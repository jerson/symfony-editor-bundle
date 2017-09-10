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
class ParameterController extends Controller
{

    /**
     * @Route("/admin/editor/parameter/reset",name="editor_parameter_reset")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetAction(Request $request)
    {
        $fs = new Filesystem();
        $defaultValues = [];
        try {
            $defaultFile = $this->get('file_locator')->locate('config/parameters.yml.dist', null, true);
            $parsed = Yaml::parse(@file_get_contents($defaultFile));
            $defaultValues = $parsed['parameters'];

        } catch (\Exception $e) {

        }
        $config = [];
        $file = $this->get('file_locator')->locate('config/parameters.yml', null, true);
        $config = Yaml::parse(@file_get_contents($file));


        foreach ($config['parameters'] as $key => $value) {
            if (isset($defaultValues[$key])) {
                $config['parameters'][$key] = $defaultValues[$key];
            }
        }

        $content = Yaml::dump($config);
        $fs->dumpFile($file, $content);
        $this->clearCache();
        return $this->redirect($this->generateUrl('editor_parameter'));

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
            $fs->remove($cacheDir . '/app' . $part . 'ProjectContainer.php');
            $fs->remove($cacheDir . '/app' . $part . 'ProjectContainer.php.meta');
            $fs->remove($cacheDir . '/app' . $part . 'ProjectContainer.php.xml');
        } catch (\Exception $e) {

        }

    }

    /**
     *
     * @Route("/admin/editor/parameter",name="editor_parameter")
     * @Security("has_role('ROLE_ADMIN')")
     * @Template()
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(Request $request)
    {
        $admin_pool = $this->get('sonata.admin.pool');
        $groups = $this->getGroups();

        if ($request->isMethod('POST')) {

            $fs = new Filesystem();
            $config = [];
            $file = $this->get('file_locator')->locate('config/parameters.yml', null, true);
            $config = Yaml::parse(@file_get_contents($file));


            $parameters = $request->get('parameters');
            $editorParameters = $this->getParameter('editor.parameter');
            foreach ($editorParameters as $key => $editorParameter) {

                $value = isset($parameters[$key]) ? $parameters[$key] : '';

                if (isset($editorParameter['type'])) {
                    if ($editorParameter['type'] === 'number') {
                        $value = (int)$value;
                    } elseif ($editorParameter['type'] === 'checkbox') {
                        $value = (bool)$value;
                    } else {
                        $value = empty($value) ? null : $value;
                    }
                } else {
                    $value = empty($value) ? null : $value;
                }
                $config['parameters'][$key] = $value;


            }

            $content = Yaml::dump($config);
            $fs->dumpFile($file, $content);
            $this->clearCache();
            if ($request->isXmlHttpRequest()) {
                return new Response('ok', 200);
            }
            return $this->redirect($this->generateUrl('editor_parameter'));

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
        $defaultValues = [];
        try {
            $defaultFile = $this->get('file_locator')->locate('config/parameters.yml.dist', null, true);
            $parsed = Yaml::parse(@file_get_contents($defaultFile));
            $defaultValues = $parsed['parameters'];

        } catch (\Exception $e) {

        }
        $file = $this->get('file_locator')->locate('config/parameters.yml', null, true);
        $config = Yaml::parse(@file_get_contents($file));


        $parameters = $this->getParameter('editor.parameter');
        $groups = [];
        foreach ($parameters as $key => $parameter) {
            $default = isset($parameter['default']) ? $parameter['default'] : (isset($defaultValues[$key]) ? $defaultValues[$key] : '');
            $group = isset($parameter['group']) ? $parameter['group'] : 'Main';
            $type = isset($parameter['type']) ? $parameter['type'] : 'text';
            $help = isset($parameter['help']) ? $parameter['help'] : '';
            $options = isset($parameter['options']) ? $parameter['options'] : [];

            if (!isset($config['parameters'][$key])) {
                $config['parameters'][$key] = '';
            }

            $value = $config['parameters'][$key];

            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            $groups[$group][] = [
                'type' => $type,
                'name' => $key,
                'help' => $help,
                'value' => $value,
                'default' => $default,
                'options' => $options
            ];
        }

        return $groups;
    }
}
