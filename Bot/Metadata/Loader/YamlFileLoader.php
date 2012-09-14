<?php

/*
 * This file is part of the VipxBotDetectBundle package.
 *
 * (c) Lennart Hildebrandt <http://github.com/lennerd>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vipx\BotDetectBundle\Bot\Metadata\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Resource\FileResource;
use Vipx\BotDetectBundle\Bot\Metadata\Metadata;
use Vipx\BotDetectBundle\Bot\Metadata\MetadataCollection;

class YamlFileLoader extends FileLoader
{

    /**
     * @param mixed $file
     * @param null|string $type
     * @return MetadataCollection
     */
    public function load($file, $type = null)
    {
        $file = $this->locator->locate($file);
        $collection = new MetadataCollection();

        $collection->addResource(new FileResource($file));

        $content = Yaml::parse($file);

        $this->parseImports($collection, $content, $file);
        $this->parseBots($collection, $content);

        return $collection;
    }

    /**
     * @param mixed $resource
     * @param null|string $type
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Parses all imports
     *
     * @param array  $content
     * @param string $file
     */
    private function parseImports(MetadataCollection $collection, $content, $file)
    {
        if (!isset($content['imports'])) {
            return;
        }

        foreach ($content['imports'] as $import) {
            $this->setCurrentDir(dirname($file));
            $collection->addCollection($this->import($import['resource'], null, isset($import['ignore_errors']) ? (Boolean) $import['ignore_errors'] : false, $file));
        }
    }

    /**
     * Parses all bot metadatas
     *
     * @param $content
     * @return array
     */
    private function parseBots(MetadataCollection $collection, $content)
    {
        if (!isset($content['bots'])) {
            return;
        }

        foreach ($content['bots'] as $name => $bot) {
            $ip = isset($bot['ip']) ? $bot['ip'] : null;
            $type = isset($bot['type']) ? $bot['type'] : Metadata::TYPE_BOT;
            $agentMatch = isset($bot['agent_match']) ? $bot['agent_match'] : Metadata::AGENT_MATCH_REGEXP;

            $collection->addMetadata(new Metadata($name, $bot['agent'], $ip, $type, $agentMatch));
        }
    }
}