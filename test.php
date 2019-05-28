<?php
use EM\EntityManager;
use Entity\ProjectMicroBit;

spl_autoload_register(function ($class_name) {
    include __DIR__ . '/' . str_replace('\\', '/', $class_name)  . '.php';
});

$em = new EntityManager(ProjectMicroBit::class);
$data = $em->getRepo()->getAll();
var_dump($data);