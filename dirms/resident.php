<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
try {$u=currentUser();} catch(Throwable $e){header('Location: login.php');exit;}
if(!$u){header('Location: login.php');exit;}
if($u['role']==='admin'){header('Location: admin.php');exit;}
$page='resident';
require __DIR__.'/index.php';
