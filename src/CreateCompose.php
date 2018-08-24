<?php
/**
 * nFrastructure (https://nfrastructure.com/)
 *
 * @link      https://github.com/nfrastructuredev/php-docker-compose for the canonical source repository
 * @copyright Copyright (c) 2018 nFrastructure (https://nfrastructure.com/)
 * @license   https://github.com/nfrastructuredev/php-docker-compose/license MIT
 */


namespace Nfrastructure\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Zend\Json\Json;

/**
 * Class CreateCompose
 */
class CreateCompose
{
    /**
     * Create the docker-compose.yml file
     *
     * @param Event $event
     */
    public static function create(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();
        if (!isset($extras['docker-compose'])) {
            throw new \InvalidArgumentException(
                'The docker-compose needs to be configured through the extra.docker-compose setting.'
            );
        }

        $dockerCompose = $extras['docker-compose'];
        if ($dockerCompose === array_values($dockerCompose)) {
            throw new \InvalidArgumentException('The extra.docker-compose must be YMAL like.');
        }

        $itr = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator(
                Json::decode(
                    Json::encode($dockerCompose['compose'])
                )
            )
        );

        static::replaceValues($itr);

        $fs = new Filesystem();

        $fs->dumpFile(
            $dockerCompose['compose-file'],
            Yaml::dump(
                $itr->getArrayCopy(),
                8,
                4,
                Yaml::DUMP_OBJECT_AS_MAP
            )
        );
    }

    /**
     * Builds an array of replacement values
     *
     * @return array
     */
    protected static function getReplace()
    {
        return [
            'HOST_IP' => static::getHostIp(),
        ];
    }

    /**
     * Gets the host computer IP address
     *
     * @return string
     */
    protected static function getHostIp()
    {
        return gethostbyname(gethostname());
    }

    /**
     * Walks the values recursively to replace template variables
     *
     * @param \RecursiveIteratorIterator $itr
     */
    public static function replaceValues(\RecursiveIteratorIterator $itr)
    {
        foreach ($itr as $key => $value) {
            $itr->getInnerIterator()->offsetSet(
                $key,
                preg_replace_callback(
                    "/\{\{(\s+)?([A-Z_]+)(\s+)?\}\}/",
                    'Nfrastructure\Composer\CreateCompose::replace',
                    $value
                )
            );
        }
    }

    /**
     * Callback for preg_replace_callback
     *
     * @param $matches
     * @return mixed
     */
    public static function replace($matches)
    {
        $replace = static::getReplace();
        $key = $matches[2];
        if (!array_key_exists($key, $replace)) {
            return $matches[0];
        }

        return str_replace($matches[0], $matches[0], $replace[$key]);
    }
}
