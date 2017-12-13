<?php 
namespace Qing\Lib;
interface ApplicationInterface{
	public  function registerRoutes($id);
	public  function registerAutoloaders($loader,$di);
	public  function registerServices($di);
}