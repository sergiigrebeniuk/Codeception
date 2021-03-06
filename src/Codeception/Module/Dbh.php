<?php
/**
 * This module replaces Db module for functional and unit testing, and requires PDO instance to be set.
 * Be default it will cover all database queries into transaction and rollback it afterwards.
 * The database should support nested transactions, in order to make cleanup work as expected.
 *
 * Pass PDO instance to this module from within your bootstrap file.
 *
 * In _bootstrap.php:
 *
 * ``` php
 * <?php
 * \Codeception\Module\Dbh::$dbh = $dbh;
 * ?>
 * ```
 *
 * This will make all queries in this connection run withing transaction and rolled back afterwards.
 *
 * Note, that you can't use this module with MySQL. Or perhaps you don't use transactions in your project, then it's ok.
 * Otherwise consider using ORMs like Doctrine, that emulate nested transactions, or switch to Db module.
 *
 * ### Configuration
 *
 * * cleanup: true - enable cleanups by covering all queries inside transaction.
 *
 */
namespace Codeception\Module;

class Dbh extends \Codeception\Module implements \Codeception\Util\DbInterface
{
    public static $dbh;

    public function _before(\Codeception\TestCase $test) {

        if (!self::$dbh) throw new \Codeception\Exception\ModuleConfig(__CLASS__,
            "Transaction module requires PDO instance explictly set.\n" .
            "You can use your bootstrap file to assign the dbh:\n\n" .
            '\Codeception\Module\Transaction::$dbh = $dbh');

        self::$dbh->beginTransaction();
    }

    public function _after(\Codeception\TestCase $test) {

        if (!self::$dbh) throw new \Codeception\Exception\ModuleConfig(__CLASS__,
            "Transaction module requires PDO instance explictly set.\n" .
            "You can use your bootstrap file to assign the dbh:\n\n" .
            '\Codeception\Module\Transaction::$dbh = $dbh');

        self::$dbh->rollback();
    }

    public function seeInDatabase($table, $criteria = array())
    {
        $res = $this->proceedSeeInDatabase($table, $criteria);
        \PHPUnit_Framework_Assert::assertGreaterThan(0, $res);
    }


    public function dontSeeInDatabase($table, $criteria = array())
    {
        $res = $this->proceedSeeInDatabase($table, $criteria);
        \PHPUnit_Framework_Assert::assertLessThan(1, $res);
    }

    protected function proceedSeeInDatabase($table, $criteria)
    {
        $query = "select count(*) from `%s` where %s";

        $params = array();
        foreach ($criteria as $k => $v) {
            $params[] = "`$k` = ?";
        }
        $params = implode('AND ',$params);

        $query = sprintf($query, $table, $params);

        $this->debugSection('Query',$query, $params);

        $sth = self::$dbh->prepare($query);
        $sth->execute(array_values($criteria));
        return $sth->fetchColumn();
    }

}
