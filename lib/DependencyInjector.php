<?php
/**
 *
 * © 2015 Tolan Blundell.  All rights reserved.
 * <tolan@patternseek.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace PatternSeek\DependencyInjector;

use Pimple\Container;
use ReflectionException;

/**
 * Inject dependencies into a class method from a Pimple container
 * Dependency objects in Pimple must be named after the dependency FQCN
 * 
 */
class DependencyInjector
{

    /**
     * @var DependencyInjector
     */
    private static $singleton;


    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    private function __construct( Container $container ){
        $this->container = $container;
    }

    /**
     * @return DependencyInjector
     * @throws \Exception
     */
    public static function instance(){
        if( ! isset( self::$singleton->container ) ){
            throw new \Exception( "Can't access DependencyInjector singleton before it is initialised via DependencyInjector::init()" );
        }
        return self::$singleton;
    }

    /**
     * @param Container $container
     */
    public static function init( Container $container ){
        $di = new DependencyInjector( $container );
        self::$singleton = $di;
    }

    /**
     * Inject dependencies from, or including, the Pimple container, into a given method on a given object
     * @param $object
     * @param $methodName
     * @return bool Whether the method was found
     */
    public function injectIntoMethod( $object, $methodName = "injectDependencies" ){
        $ref = new \ReflectionClass($object);
        try{
            $refMethod = $ref->getMethod( $methodName );
        }catch( ReflectionException  $e ){
            return null;
        }
        
        $toInject = [];
        foreach ($refMethod->getParameters() as $p) {
            $className = $p->getClass()->name;
            if( $className === "Pimple\\Container" ){
                $toInject[] = $this->container;
            }else{
                $toInject[] = $this->container[ $className ];
            }
        }
        return $refMethod->invoke( $object, ... $toInject );
    }


}