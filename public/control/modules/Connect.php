<?php

namespace App;

use PDO;

class Connect
{
    private $dbHost;
    private $dbUser;
    private $dbPass;
    private $dbName;

    /** @var PDO */
    public $con;

    function __construct(array $data)
    {
        $this->dbHost = $data[ 'dbHost' ];
        $this->dbName = $data[ 'dbName' ];
        $this->dbUser = $data[ 'dbUser' ];
        $this->dbPass = $data[ 'dbPass' ];

        $this->connect();
    }

    public function connect()
    {

        $this->con = new \PDO('mysql:host=' . $this->dbHost . ';dbname=' . $this->dbName, $this->dbUser, $this->dbPass);

        if (!$this->con) {
            echo 'CANNOT CONNECT';
        }

        $this->con->exec("set names utf8");

    }

    public function query(string $sql)
    {
        //echo $sql.'<br>';
        return $this->con->query($sql);
    }

    public function fetch($obj)
    {
        if (!$obj) {
            return false;
        }
        $array = $obj->fetch(PDO::FETCH_ASSOC);

        return $array;
    }

    public function numRows($obj)
    {
        $result = $obj->rowCount();

        return $result;
    }

    public function getLastInsertedId(): string
    {
        return $this->con->lastInsertId();
    }

    public function jsonDecode(string $json): ?array
    {
        $data = preg_replace('/[[:cntrl:]]/', '', $json);
        $data = json_decode($data, true);

        return $data;
    }

}


$db = new Connect(['dbHost' => 'hosting.ilkinalibayli.com',
    'dbUser' => 'ilkinali_2bakude',
    'dbPass' => 'oN?Qb7I)&3;M',
    'dbName' => 'ilkinali_2bakude']);
