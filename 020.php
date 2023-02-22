<?php

/** @dnoinspection AutoloadingIssuesInspection */

declare(strict_types=1);

namespace p019;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$connection = [
    'driver' => 'pdo_pgsql',
    'host' => 'api-postgres',
    'port' => 5432,
    'user' => 'app',
    'password' => 'secret',
    'dbname' => 'app',
    'charset' => ' utf-8',
];

$connection2 = [
    'driver' => 'pdo_pgsql',
    'url' => 'pgsql://app:secret@api-postgres:5432/app',
    'charset' => ' utf-8',
];

$pdo = new \PDO('pgsql', 'username', 'password');
$connection3 = [
    'pdo' => $pdo,
];

$paths = [
    'src/Auth/Entity' ,
];

$isDevMode = getenv('APPENV') !== 'prod';

#################################

$config = Setup::createAnnotationMetadataConfigurationd($paths, $isDevMode);

$em = EntityManager::create($connection, $config);

#################################

$user = User:: requestJoinByEmail(
    $id = Id::generated,
    $date = new DateTimeImmutable(),
    new Email('mail@example.com'),
    'hash',
    $token = new Token(Uuid::uuid4()->toString(), $date->modify('+ 1 hour'))
);

/**
 * EM содержит внутри себя объект Unit of Work, который реализует одноименный паттерн Unit of Work.
 * Это значит что мы у себя можем создавать сколько угодно сущностей, отправлять их EM и только в конце вызвать
 * метод flush.
 *
 * Паттерн Unit of Work так и работает, что он сначала накапливает всю работу внутри себя, которую ему нужно сделать
 * и только когда мы вызываем метод commit или flush, то только в этом случае он производит всю работу, которую
 * накопил.
 *
 * Метод persist и remove просто добавляет сущность в приватный массив.
 * Когда вызывается метод persist Em добавляет объекты в EntitiesForInsert
 * Когда вызывается метод remove Em добавляет объекты в EntitiesDelete
 *
 * Когда вызываем метод flush EM делает три вещи:
 * 1) Смотрит какие сущности есть на добавление и вставляет их в бд через insert
 * 2) Смотрит какие сущности есть на удаление и удаляет их в бд через delete
 * 3) Тоже самое когда происходит update
 *
 * Дальше нужно вызвать метод flush и произойдет некая магия.
 *
 * Когда мы ищем сущность через find EM, он создает объект и его возвращает, но помимо этого он его добавляет
 * в некий свой приватный массив IdentityMap, укаывая что у такого класса по такому id хранится такая сущность
 *
 * $user = $em->find(User::class, $id);
 * $user = $em->find(User::class, $id);
 * $user = $em->find(User::class, $id);
 * $user = $em->find(User::class, $id);
 *
 * И соответсвено если мы будем вызывать эту же сущность по этому id, то нам будет возвращаться один и тот же объект, который
 * был собран всего 1 раз по запросу, который отправился в бд.
 *
 */
$em->persist($user);
$em->flush();

#################################

$user = $em->find(User::class, $id);

$user->confirmJoin($token->getValue(), new DateTimeImmutable());

$em->flush();

#################################













































