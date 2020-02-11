<?php namespace Omt\TranslationManager;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Omt\TranslationManager\Models\Translation;
use Illuminate\Support\Collection;
use Omt\TranslationManager\Libs\TenantLibs;
use Tanmuhittin\LaravelGoogleTranslate\Commands\TranslateFilesCommand;

class Controller extends BaseController
{
    /** @var \Barryvdh\TranslationManager\Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getIndex($group = null)
    {
        $tenant_id = TenantLibs::getCurrentTenantId();

        $locales = $this->manager->getLocales($tenant_id);
        $groups = Translation::where('tenant_id', $tenant_id)->groupBy('group');
        $excludedGroups = $this->manager->getConfig('exclude_groups');
        if ($excludedGroups) {
            $groups->whereNotIn('group', $excludedGroups);
        }

        $groups = $groups->select('group')->orderBy('group')->get()->pluck('group', 'group');
        if ($groups instanceof Collection) {
            $groups = $groups->all();
        }
        $groups = collect(['' => 'Choose a group'])->merge($groups);
        $numChanged = Translation::where('tenant_id', $tenant_id)->where('group', $group)->where('status', Translation::STATUS_CHANGED)->count();


        $allTranslations = Translation::where('tenant_id', $tenant_id)->where('group', $group)->orderBy('key', 'asc')->get();
        $numTranslations = count($allTranslations);
        $translations = [];
        foreach ($allTranslations as $translation) {
            $translations[$translation->key][$translation->locale] = $translation;
        }

        return view('translation-manager::index')
            ->with('translations', $translations)
            ->with('locales', $locales)
            ->with('groups', $groups)
            ->with('group', $group)
            ->with('numTranslations', $numTranslations)
            ->with('numChanged', $numChanged)
            ->with('editUrl', $group ? action('\Omt\TranslationManager\Controller@postEdit', [$group]) : null)
            ->with('deleteEnabled', $this->manager->getConfig('delete_enabled'));
    }

    public function getView($group = null)
    {
        return $this->getIndex($group);
    }

    protected function loadLocales()
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        //Set the default locale as the first one.
        $locales = Translation::where('tenant_id', $tenant_id)->groupBy('locale')
            ->select('locale')
            ->get()
            ->pluck('locale');

        if ($locales instanceof Collection) {
            $locales = $locales->all();
        }
        $locales = array_merge([config('app.locale')], $locales);
        return array_unique($locales);
    }

    public function postAdd($group = null)
    {
        $keys = explode("\n", request()->get('keys'));

        foreach ($keys as $key) {
            $key = trim($key);
            if ($group && $key) {
                $this->manager->missingKey('*', $group, $key);
            }
        }
        return redirect()->back();
    }

    public function postEdit($group = null)
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        if (!in_array($group, $this->manager->getConfig('exclude_groups'))) {
            $name = request()->get('name');
            $value = request()->get('value');

            list($locale, $key) = explode('|', $name, 2);
            $translation = Translation::firstOrNew([
                'tenant_id' => $tenant_id,
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
            ]);
            $translation->value = (string)$value ?: null;
            $translation->status = Translation::STATUS_CHANGED;
            $translation->save();
            return array('status' => 'ok');
        }
    }

    public function postDelete($group = null, $key)
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        if (!in_array($group, $this->manager->getConfig('exclude_groups')) && $this->manager->getConfig('delete_enabled')) {
            Translation::where('tenant_id', $tenant_id)->where('group', $group)->where('key', $key)->delete();
            return ['status' => 'ok'];
        }
    }

    public function postImport(Request $request)
    {
        $replace = $request->get('replace', false);
        $counter = $this->manager->importTranslations($replace);

        return ['status' => 'ok', 'counter' => $counter];
    }

    public function postFind()
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        $numFound = $this->manager->findTranslations($tenant_id);

        return ['status' => 'ok', 'counter' => (int)$numFound];
    }

    public function postPublish($group = null)
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        $json = false;

        if ($group === '_json') {
            $json = true;
        }

        $this->manager->exportTranslations($tenant_id, $group, $json);

        return ['status' => 'ok'];
    }

    public function postAddGroup(Request $request)
    {
        $group = str_replace(".", '', $request->input('new-group'));
        if ($group) {
            return redirect()->action('\Omt\TranslationManager\Controller@getView', $group);
        } else {
            return redirect()->back();
        }
    }

    public function postAddLocale(Request $request)
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        $locales = $this->manager->getLocales($tenant_id);
        $newLocale = str_replace([], '-', trim($request->input('new-locale')));
        if (!$newLocale || in_array($newLocale, $locales)) {
            return redirect()->back();
        }
        $this->manager->addLocale($newLocale);
        return redirect()->back();
    }

    public function postRemoveLocale(Request $request)
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        foreach ($request->input('remove-locale', []) as $locale => $val) {
            $this->manager->removeLocale($tenant_id, $locale);
        }
        return redirect()->back();
    }

    public function postTranslateMissing(Request $request)
    {
        $tenant_id = TenantLibs::getCurrentTenantId();
        $locales = $this->manager->getLocales($tenant_id);
        $newLocale = str_replace([], '-', trim($request->input('new-locale')));
        if ($request->has('with-translations') && $request->has('base-locale') && in_array($request->input('base-locale'), $locales) && $request->has('file') && in_array($newLocale, $locales)) {
            $base_locale = $request->get('base-locale');
            $group = $request->get('file');
            $base_strings = Translation::where('tenant_id', $tenant_id)->where('group', $group)->where('locale', $base_locale)->get();
            foreach ($base_strings as $base_string) {
                $base_query = Translation::where('tenant_id', $tenant_id)->where('group', $group)->where('locale', $newLocale)->where('key', $base_string->key);
                if ($base_query->exists() && $base_query->whereNotNull('value')->exists()) {
                    // Translation already exists. Skip
                    continue;
                }
                $translated_text = TranslateFilesCommand::translate($base_locale, $newLocale, $base_string->value);
                request()->replace([
                    'value' => $translated_text,
                    'name' => $newLocale . '|' . $base_string->key,
                ]);
                app()->call(
                    'Omt\TranslationManager\Controller@postEdit',
                    [
                        'group' => $group
                    ]
                );
            }
            return redirect()->back();
        }
        return redirect()->back();
    }
}
