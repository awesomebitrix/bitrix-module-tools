<?php

namespace WS\Tools\ORM\Db;

use WS\Tools\ORM\Db\Gateway\BitrixOrmElement;
use WS\Tools\ORM\Db\Gateway\Common;
use WS\Tools\ORM\Db\Gateway\Enum;
use WS\Tools\ORM\Db\Gateway\File;
use WS\Tools\ORM\Db\Gateway\IblockElement;
use WS\Tools\ORM\Db\Gateway\User;
use WS\Tools\ORM\Db\Request\Select;
use WS\Tools\ORM\Entity;
use WS\Tools\Cache\CacheManager;

/**
 * �������� ������ � ����� ������;
 *
 * @author ������ ����������� (my.sokolovsky@gmail.com)
 */
class Manager {

    private $gateways = array();
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @return array
     */
    private function engines() {
        return array(
            'iblockElement' => IblockElement::className(),
            'common' => Common::className(),
            'list' => Enum::className(),
            'file' => File::className(),
            'user' => User::className(),
            'bitrixOrmElement' => BitrixOrmElement::className(),
        );
    }

    public static function className() {
        return get_called_class();
    }

    /**
     * @param CacheManager $cache
     */
    public function __construct(CacheManager $cache) {
        $this->cacheManager = $cache;
    }

    /**
     * ��������� ���������� ����� ��������
     *
     * @param string $entityClass ����� ��������
     * @return Gateway
     * @throws \Exception
     */
    public function getGateway($entityClass) {
        $entityClass = ltrim($entityClass, '\\');
        if ($this->gateways[$entityClass]) {
            return $this->gateways[$entityClass];
        }

        $entityAnalyzer = new EntityAnalyzer(
            $entityClass,
            $this->cacheManager->getArrayCache('meta_'.get_class($this).'_'.$entityClass, 86400)
        );

        $engines = self::engines();
        $gatewayClass = $engines[$entityAnalyzer->gateway()];
        if (!$gatewayClass || !class_exists($gatewayClass)) {
            throw new \Exception('��� ��������� `'.$entityClass.'` �� ��������� ���� ������');
        }
        $this->gateways[$entityClass] = new $gatewayClass($this, $entityAnalyzer);
        return $this->gateways[$entityClass];
    }

    /**
     * @param $entityClass
     * @param $id
     * @return object
     * @throws \Exception
     */
    public function getById($entityClass, $id) {
        $collection = $this->getGateway($entityClass)->findByIds(array($id));
        return $collection->getFirst();
    }

    /**
     * @param $entityClass
     * @return Select
     * @throws \Exception
     */
    public function createSelectRequest($entityClass) {
        return new Select($this->getGateway($entityClass));
    }

    /**
     * @param array|\Traversable|Entity $entities
     * @param bool $withRelations
     * @throws \Exception
     */
    public function save($entities, $withRelations = false) {
        if (is_object($entities) && $entities instanceof Entity) {
            $entities = array($entities);
        }
        if ($entities instanceof \Traversable || is_array($entities)) {
            foreach ($entities as $entity) {
                if (!is_object($entity) || !$entity instanceof Entity) {
                    throw new \Exception("Saving object is not entity");
                }
                $gw = $this->getGateway(get_class($entity));
                $gw->save($entity, $withRelations);
            }
        }
    }

    /**
     * @param $entities
     * @throws \Exception
     */
    public function remove($entities) {
        if (is_object($entities) && $entities instanceof Entity) {
            $entities = array($entities);
        }
        if ($entities instanceof \Traversable || is_array($entities)) {
            foreach ($entities as $entity) {
                if (!is_object($entity) || !$entity instanceof Entity) {
                    throw new \Exception("Removing object is not entity");
                }
                $gw = $this->getGateway(get_class($entity));
                $gw->remove($entity);
            }
        }
    }

    /**
     * Unset entities from gateways state
     *
     * @param string|null $entityClass
     * @throws \Exception
     */
    public function refresh($entityClass = null) {
        $gateways = $this->gateways;
        $entityClass && $gateways = array($this->getGateway($entityClass));
        /** @var Gateway $gateway */
        foreach ($gateways as $gateway) {
            $gateway->refresh();
        }
    }

    /**
     * @param $entityClass
     * @param $id
     * @return Entity
     * @throws \Exception
     */
    public function createProxy($entityClass, $id) {
        $gw = $this->getGateway($entityClass);
        return $gw->getProxy($id);
    }
}
