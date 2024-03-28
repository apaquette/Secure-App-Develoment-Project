<?php declare(strict_types=1);
    use PHPUnit\Framework\TestCase;

    class DatabaseTest extends TestCase{
        private function SetDatabase(): void{
            $tempConn = new PDO("mysql:host=localhost", "TEST", "");
            $existingDatabases = $tempConn->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('secureappdev', $existingDatabases)){
                $database = new Database();
                $database->Create(); //create database to ensure it exists

            }
            $tempConn = null;
        }

        private function DropDatabase(): void{
            $tempConn = new PDO("mysql:host=localhost", "TEST", "");
            $existingDatabases = $tempConn->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('secureappdev', $existingDatabases)){
                $tempConn->exec("DROP DATABASE secureappdev");//drop database to simulate no database exists scenario
            }
            $tempConn = null;
        }

        public function testGetConnection_TestCase1():void{
            $this->SetDatabase();
            
            $database = new Database();
            $this->assertInstanceOf(PDO::class, $database->GetConnection());
        }

        public function testGetConnection_TestCase2():void{
            $this->DropDatabase();

            $database = new Database();
            $this->expectException(PDOException::class);
            $database->GetConnection();
        }

        public function testCreate_TestCase1():void{
            $this->DropDatabase();
            
            $database = new Database();
            $exception = null;

            try{
                $database->Create();
            }catch(Exception $e){
                $exception = $e;
            }

            $this->assertNull($exception, 'Exception was thrown');
        }

        public function testCreateSuccessMsg_TestCase1():void{
            $msg = "<br>Database created successfully<br>Table 'users' created successfully<br>Admin Added (Username = admin, Password =AdminPass1!<br>User Added (Username = user1, Password =Password1!<br>";
            $this->expectOutputString($msg);
            $database = new Database();
            $database->CreateSuccessMsg();
        }

        public function testProcessQuery_TestCase1():void{
            $this->SetDatabase();

            $database = new Database();
            $query = "SELECT * FROM sapusers";
            $this->assertInstanceOf(PDOStatement::class, $database->ProcessQuery($query));
        }

        public function testProcessQuery_TestCase2():void{
            $this->SetDatabase();
            
            $database = new Database();
            $query = "SELECT * FROM sapusers WHERE user_admin = ?";
            $param = [1];
            $this->assertInstanceOf(PDOStatement::class, $database->ProcessQuery($query, $param));
        }

        public function testProcessQuery_TestCase3():void{
            $this->SetDatabase();
            
            $database = new Database();
            $query = "SELECT ? FROM sapusers WHERE user_admin = ? OR user_admin = ?";
            $param = ["user_salt", 1, 0];
            $this->assertInstanceOf(PDOStatement::class, $database->ProcessQuery($query, $param));
        }

        public function testProcessQuery_TestCase4():void{
            $this->SetDatabase();
            
            $database = new Database();
            $query = "foobar";
            $this->expectException(PDOException::class);
            $database->ProcessQuery($query);
        }

        public function testProcessQuery_TestCase5():void{
            $this->SetDatabase();
            
            $database = new Database();
            $query = "foobar";
            $param = [1,2,3];
            $this->expectException(PDOException::class);
            $database->ProcessQuery($query, $param);
        }

        public function testProcessQuery_TestCase6():void{
            $this->SetDatabase();
            
            $database = new Database();
            $query = "SELECT ? FROM sapusers WHERE user_admin = ? OR user_admin = ?";
            $param = ["user_salt", 1, 0, 1,2,3,4];
            $this->expectException(PDOException::class);
            $database->ProcessQuery($query, $param);
        }
    }
?>