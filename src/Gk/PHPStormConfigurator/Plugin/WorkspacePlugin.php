<?php

namespace Gk\PHPStormConfigurator\Plugin;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class WorkspacePlugin extends AbstractXMLPlugin
{
    protected function getXMLTemplate()
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
</project>
XML;
    }

    protected function getConfigFileName()
    {
        return 'workspace.xml';
    }

    public function getName()
    {
        return 'workspace';
    }

    public function addToFavorites($path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf("Cannot add path '%s' to favorites because it doesn't exit", $path));
        }

        $componentNode = $this->ensureChild($this->getProjectNode(), 'component', ['name' => 'FavoritesManager']);
        $listNode = $this->ensureChild($componentNode, 'favorites_list', ['name' => $this->configurator->getProjectName()]);

        $rootFavorite = new \SplFileInfo($path);
        $rootNode = $this->ensureChild($listNode, 'favorite_root', $this->getFavoriteAttributes($rootFavorite));

        if (!$rootFavorite->isDir()) {
            return;
        }

        $finder = new Finder();
        $finder->in($path);

        $directoryNodes = [];
        foreach ($finder as $favorite) {
            /** * @var SplFileInfo $favorite */
            $favoritePath = $favorite->getPath();
            if (isset($directoryNodes[$favoritePath])) {
                $parentNode = $directoryNodes[$favoritePath];
            } else {
                $parentNode = $rootNode;
            }

            $favoriteNode = $this->ensureChild($parentNode, 'favorite_root', $this->getFavoriteAttributes($favorite));

            if ($favorite->isDir()) {
                $directoryNodes[$favorite->getPathname()] = $favoriteNode;
            }
        }

        return $this;
    }

    protected function getFavoriteAttributes(\SplFileInfo $favorite)
    {
        $url = preg_replace(sprintf('@^%s@', $this->configurator->getPath()), 'file://$PROJECT_DIR$', $favorite->getPathname());

        $attributes = ['url' => $url];
        if ($favorite->isDir()) {
            $attributes['type'] = 'directory';
            $attributes['module'] = $this->configurator->getProjectName();
            $attributes['klass'] = 'com.intellij.ide.projectView.impl.nodes.PsiDirectoryNode';
        } else {
            $attributes['type'] = 'psiFile';
            $attributes['klass'] = 'com.intellij.ide.projectView.impl.nodes.PsiFileNode';
        }

        return $attributes;
    }

    protected function getProjectNode()
    {
        return $this->dom->childNodes->item(0);
    }

}
