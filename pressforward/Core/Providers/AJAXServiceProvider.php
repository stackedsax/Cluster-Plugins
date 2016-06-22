<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
//use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\AJAX\ConfigurationAJAX;
use PressForward\Core\AJAX\ItemsAJAX;
use PressForward\Core\AJAX\SourceAJAX;
use PressForward\Core\AJAX\NominationsAJAX;

class AJAXServiceProvider extends ServiceProvider {


	public function register( Container $container ){
		$container->share(
			'ajax.configuration',
			function( $container ){
				return new ConfigurationAJAX( $container->fetch('controller.metas'), $container->fetch('controller.items'), $container->fetch('schema.feed_item') );
			}
		);

        $container->share(
            'ajax.items',
            function( $container ){
                return new ItemsAJAX( $container->fetch('controller.metas'), $container->fetch('controller.items'), $container->fetch('schema.feed_item') );
            }
        );

        $container->share(
            'ajax.source',
            function( $container ){
                return new SourceAJAX( $container->fetch('controller.readability'), $container->fetch('utility.retrieval'), $container->fetch('schema.feed_item') );
            }
        );

        $container->share(
            'ajax.nominations',
            function( $container ){
                return new NominationsAJAX( $container->fetch('controller.metas'), $container->fetch('controller.items'), $container->fetch('schema.feed_item') );
            }
        );

    }

}
