<?php
  spl_autoload_register('KargoShipping_autoloader');
  function KargoShipping_autoloader( $class ) {
    $namespace = 'KARGOSHIPPING';

    if ( strpos( $class, $namespace ) !== 0 ) 
		  return;

    $class_name = substr( $class, strlen( $namespace ) );
	  
    // Trim leading backslash if present
	  $class_name = ltrim( $class_name, '\\' );

    $class_file = __DIR__ . '/' . str_replace( '\\', '/', $class_name ) . '.php';

    if (file_exists($class_file))
      require_once($class_file);


  }
