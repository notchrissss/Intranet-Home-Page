<?php namespace App\Controllers;

class Migrate extends \CodeIgniter\Controller
{

  public function index()
  {
    $migrate = \Config\Services::migrations();

    try
    {
      $migrate->latest();
    }
    catch (\Throwable $e)
    {
      echo "Error migrating database";
    }
  }

}
