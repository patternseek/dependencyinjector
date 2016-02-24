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
     * @return mixed The method's return value
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

    /**
     * Inject dependencies from, or including, the Pimple container, into a a given class's constructor
     * Optionally providing a number of arguments to the constructor to prepend.
     *
     * @param string $classToBuildName
     * @param array $toInject
     * @return bool Whether the method was found
     */
    public function injectIntoConstructor( $classToBuildName, array $toInject=[] ){
        $ref = new \ReflectionClass($classToBuildName);
        try{
            $constr = $ref->getConstructor();
        }catch( ReflectionException  $e ){
            return null;
        }

        $numToSkip = count($toInject);
        foreach ($constr->getParameters() as $p) {
            if( $numToSkip > 0 ){
                $numToSkip--;
                continue;
            }
            $classToInjectName = $p->getClass()->name;
            if( $classToInjectName === "Pimple\\Container" ){
                $toInject[] = $this->container;
            }else{
                $toInject[] = $this->container[ $classToInjectName ];
            }
        }
        return new $classToBuildName( ... $toInject );
    }

}