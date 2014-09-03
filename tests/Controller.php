<?php
class Controller {
	private $redirect;

	public function __construct ($redirect) {
		$this->redirect = $redirect; 
	}

	public function sampleRedirect () {
        return $this->redirect->action('controller@sampleOutput4');
    }

    public function sampleOutput () {
        echo 'SAMPLE';
    }

    public function sampleOutput2 ($data) {
        echo 'SAMPLE' . $data;
    }

    public function sampleOutput3 ($name, $age, $location) {
        echo 'Name: ' . $name . ' Age: ' . $age . ' Location: ' . $location;
    }

    public function sampleOutput4 () {
        echo 'From Redirect';
    }

    public function beforeFilter () {
        echo 'START';
        return true;
    }

    public function afterFilter () {
        echo 'END';
        return true;
    }
}