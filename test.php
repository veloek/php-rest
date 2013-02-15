<?php

class ParentClass {
	public function __call($name, $args) {
		$className = get_class($this);
		return call_user_func_array(array($className, 'getSomething'), $args);
	}
}

class Child extends ParentClass {
	
	/** @Get */
	public function getSomething() {
		return "something";
	}

}

$var = new Child();

$ret = $var->get();

echo $ret; 

?>
