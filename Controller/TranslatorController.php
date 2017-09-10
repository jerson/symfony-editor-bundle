<?php

namespace EditorBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * {@inheritDoc}
 */
class TranslatorController extends Controller
{


    /**
     * @Route("/admin/editor/translator/reset",name="editor_translator_reset")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetAction(Request $request)
    {

        $parameters = $this->getParameter('editor.translator');
        $groups = [];
        $fs = new Filesystem();
        foreach ($parameters as $name => $parameter) {
            $bundle = isset($parameter['bundle']) ? $parameter['bundle'] . '/' : '';
            $domain = isset($parameter['domain']) ? $parameter['domain'] : 'messages';
            $locales = isset($parameter['locales']) ? explode(',', $parameter['locales']) : [];

            foreach ($locales as $locale) {

                $filename = sprintf('%sResources/translations/%s.yml', $bundle, $domain);
                $baseFile = $this->get('file_locator')->locate($filename, null, true);
                $file = str_replace('.yml', '.' . $locale . '.yml', $baseFile);

                if ($fs->exists($file . '.bak')) {

                    if ($fs->exists($file)) {
                        $fs->remove($file);
                    }

                    $fs->copy($file . '.bak', $file, true);
                }

            }

        }

        $this->clearCache();
        return $this->redirect($this->generateUrl('editor_translator'));

    }

    /**
     *
     */
    private function clearCache()
    {
        $cacheDir = $this->getParameter('kernel.cache_dir');
        $env = $this->getParameter('kernel.environment');

        $fs = new Filesystem();
        try {
            $part = $env === 'dev' ? 'DevDebug' : 'Prod';
            $fs->remove($cacheDir . '/translations');
        } catch (\Exception $e) {

        }

    }

    /**
     * @Route("/admin/editor/translator",name="editor_translator")
     * @Security("has_role('ROLE_ADMIN')")
     * @Template()
     * @param Request $request
     * @return array|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(Request $request)
    {
        $admin_pool = $this->get('sonata.admin.pool');
        $groups = $this->getGroups();


        if ($request->isMethod('POST')) {

            $fs = new Filesystem();
            $parameters = $this->getParameter('editor.translator');
            foreach ($parameters as $name => $parameter) {
                $bundle = isset($parameter['bundle']) ? $parameter['bundle'] . '/' : '';
                $domain = isset($parameter['domain']) ? $parameter['domain'] : 'messages';
                $locales = isset($parameter['locales']) ? explode(',', $parameter['locales']) : [];

                $filename = sprintf('%sResources/translations/%s.yml', $bundle, $domain);
                $baseFile = $this->get('file_locator')->locate($filename, null, true);

                $translationsByName = $request->get($name);
                foreach ($locales as $locale) {

                    if (!isset($translationsByName[$locale])) {
                        continue;
                    }


                    $translations = $translationsByName[$locale];
                    foreach ($translations as $key => $translation) {
                        if (empty($translation)) {
                            unset($translations[$key]);
                        }
                    }
                    $file = str_replace('.yml', '.' . $locale . '.yml', $baseFile);
                    if (empty($translations) && $fs->exists($file)) {
                        $fs->dumpFile($file, '');
                    } elseif (!empty($translations)) {
                        $data = Yaml::dump($translations);
                        $fs->dumpFile($file, $data);

                    }


                }

            }
            $this->clearCache();
            if ($request->isXmlHttpRequest()) {
                return new Response('ok', 200);
            }
            return $this->redirect($this->generateUrl('editor_translator'));

        }
        return [
            'admin_pool' => $admin_pool,
            'groups' => $groups,
        ];

    }

    /**
     * @return array
     */
    private function getGroups()
    {
        $parameters = $this->getParameter('editor.translator');
        $groups = [];
        $fs = new Filesystem();

        foreach ($parameters as $name => $parameter) {
            $bundle = isset($parameter['bundle']) ? $parameter['bundle'] . '/' : '';
            $domain = isset($parameter['domain']) ? $parameter['domain'] : 'messages';
            $locales = isset($parameter['locales']) ? explode(',', $parameter['locales']) : [];

            $groups[$name] = [
                'locales' => $locales,
                'keys' => [],
            ];

            $originalFilename = sprintf('%sResources/translations/%s.yml', $bundle, $domain);
            $baseFile = $this->get('file_locator')->locate($originalFilename, null, true);
            $keys = Yaml::parse(@file_get_contents($baseFile));
            $groups[$name]['keys'] = empty($keys) ? [] : $keys;

            foreach ($locales as $locale) {

                $file = str_replace('.yml', '.' . $locale . '.yml', $baseFile);
                $translations = [];
                try {
                    if ($fs->exists($file)) {
                        $translations = Yaml::parse(@file_get_contents($file));
                        if (!$fs->exists($file . '.bak')) {
                            $fs->copy($file, $file . '.bak', false);
                        }
                    }
                } catch (\Exception $e) {

                }

                foreach ($groups[$name]['keys'] as $key => $value) {
                    $groups[$name]['keys'][$key][$locale] = isset($translations[$key]) ? $translations[$key] : null;
                }


            }


        }

        return $groups;
    }

}
