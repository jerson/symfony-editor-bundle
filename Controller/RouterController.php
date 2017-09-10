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
class RouterController extends Controller
{

    /**
     * @Route("/admin/editor/router/reset",name="editor_router_reset")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetAction(Request $request)
    {
        $collections = $this->getCollections();

        $fs = new Filesystem();

        foreach ($collections as $key => $collection) {

            foreach ($collection['items'] as $keyRoute => $route) {

                if (
                    isset($route['defaults']) &&
                    isset($route['defaults']['original'])
                ) {
                    $collection['items'][$keyRoute]['path'] = $route['defaults']['original'];
                }
            }

            $type = $collection['type'];
            $resource = $collection['resource'];
            $file = $this->get('file_locator')->locate($resource, null, true);
            $content = Yaml::dump($collection['items']);
            $fs->dumpFile($file, $content);

        }

        $this->clearCache();
        return $this->redirect($this->generateUrl('editor_router'));

    }

    /**
     * @return array
     */
    private function getCollections()
    {
        $routes = $this->getParameter('editor.router');
        $collections = [];
        foreach ($routes as $key => $route) {
            $type = isset($route['type']) ? $route['type'] : 'yaml';
            $collection = $this->getCollection($route['resource'], $type);
            $collections[$key] = $collection;
        }

        return $collections;
    }

    /**
     * @param $resource
     * @param $type
     * @return array
     */
    private function getCollection($resource, $type)
    {
        $collection = [
            'resource' => $resource,
            'type' => $type,
            'items' => []
        ];
        $file = $this->get('file_locator')->locate($resource, null, true);
        $collection['items'] = Yaml::parse(file_get_contents($file));

        return $collection;

//        $collection = new \Symfony\Component\Routing\RouteCollection();
//        $importedRoutes = $this->get('routing.loader')->import($resource, $type);
//        $collection->addCollection($importedRoutes);
//        return $collection;
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
            $fs->remove($cacheDir . '/app' . $part . 'ProjectContainerUrlMatcher.php');
            $fs->remove($cacheDir . '/app' . $part . 'ProjectContainerUrlMatcher.php.meta');
            $fs->remove($cacheDir . '/app' . $part . 'ProjectContainerUrlGenerator.php');
            $fs->remove($cacheDir . '/app' . $part . 'ProjectContainerUrlGenerator.php.meta');
        } catch (\Exception $e) {

        }

    }

    /**
     *
     * @Route("/admin/editor/router",name="editor_router")
     * @Security("has_role('ROLE_ADMIN')")
     * @Template()
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(Request $request)
    {
        $admin_pool = $this->get('sonata.admin.pool');
        $collections = $this->getCollections();

        if ($request->isMethod('POST')) {

            $fs = new Filesystem();

            foreach ($collections as $key => $collection) {
                $params = $request->get($key);

                foreach ($params as $keyParam => $param) {

                    if (isset($collection['items'][$keyParam])) {
                        $collection['items'][$keyParam]['path'] = '/' . ltrim($param);
                    }
                }

                $type = $collection['type'];
                $resource = $collection['resource'];
                $file = $this->get('file_locator')->locate($resource, null, true);
                $content = Yaml::dump($collection['items']);
                $fs->dumpFile($file, $content);

            }

            $this->clearCache();
            if ($request->isXmlHttpRequest()) {
                return new Response('ok', 200);
            }
            return $this->redirect($this->generateUrl('editor_router'));

        }

        return [
            'admin_pool' => $admin_pool,
            'collections' => $collections,
        ];

    }
}
