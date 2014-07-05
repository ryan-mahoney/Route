<?php return array (
  0 => 
  array (
    '/sample' => 
    array (
      'GET' => 
      array (
        0 => '\\Opine\\RouteTest',
        1 => 'sampleOutput',
      ),
    ),
  ),
  1 => 
  array (
    0 => 
    array (
      'regex' => '~^(?|/sample/([^/]+))$~',
      'routeMap' => 
      array (
        2 => 
        array (
          'GET' => 
          array (
            0 => 
            array (
              0 => '\\Opine\\RouteTest',
              1 => 'sampleOutput2',
            ),
            1 => 
            array (
              'input' => 'input',
            ),
          ),
        ),
      ),
    ),
  ),
);