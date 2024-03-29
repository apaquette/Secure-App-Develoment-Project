<?php declare(strict_types=1);
    use PHPUnit\Framework\TestCase;

    class LoginManagerTest extends TestCase{
        private function SetDatabase(): void{
            $tempConn = new PDO("mysql:host=localhost", "TEST", "");
            $existingDatabases = $tempConn->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('secureappdev', $existingDatabases)){
                $database = Database::getInstance();
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

        public function testProcessLogin_TestCase1():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $pwd = "Password1!";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertTrue($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase2():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $pwd = "foobar";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase3():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "foo";
            $pwd = "bar";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase4():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $pwd = "";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase5():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "";
            $pwd = "";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase6():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "foo";
            $pwd = "";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase7():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "";
            $pwd = "foobar";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase8():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = null;
            $pwd = "Password1!";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase9():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = null;
            $pwd = null;
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessLogin_TestCase10():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "admin";
            $pwd = "password' OR '1'='1' --";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testFailedLogin_TestCase1():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->FailedLogin($uid, $ipAddr));

            $this->DropDatabase();
        }

        public function testFailedLogin_TestCase2():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = null;
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->FailedLogin($uid, $ipAddr));

            $this->DropDatabase();
        }

        public function testFailedLogin_TestCase3():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "";
            $ipAddr = $loginManager->GetIpAddress();
            $this->AssertFalse($loginManager->FailedLogin($uid, $ipAddr));

            $this->DropDatabase();
        }

        public function testResetPassword_TestCase1():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $oldpass = "Password1!";
            $newpass = "Password2!";
            $newConfirm = "Password2!";
            $this->AssertTrue($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm));

            $this->DropDatabase();
        }

        public function testResetPassword_TestCase2():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $oldpass = "Password1!";
            $newpass = "Password2!";
            $newConfirm = "Password3!";
            $this->AssertFalse($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm));

            $this->DropDatabase();
        }

        public function testResetPassword_TestCase3():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $oldpass = "Password10!";
            $newpass = "Password2!";
            $newConfirm = "Password2!";
            $this->AssertFalse($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm));

            $this->DropDatabase();
        }

        public function testResetPassword_TestCase4():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $oldpass = "Password1!";
            $newpass = "Word2!";
            $newConfirm = "Word2!";
            $this->AssertFalse($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm));

            $this->DropDatabase();
        }

        public function testResetPassword_TestCase5():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "userOne";
            $oldpass = "Password1!";
            $newpass = "Password2!";
            $newConfirm = "Password2!";
            $this->AssertFalse($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm));

            $this->DropDatabase();
        }

        public function testResetPassword_TestCase6():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $oldpass = "Password1!";
            $newpass = "password2!";
            $newConfirm = "password2!";
            $this->AssertFalse($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm));

            $this->DropDatabase();
        }

        public function testResetPassword_TestCase7():void{
            $this->SetDatabase();
            
            $loginManager = LoginManager::getInstance();
            $uid = "user1";
            $oldpass = "Password1!";
            $newpass = "password!!";
            $newConfirm = "password!!";
            $this->AssertFalse($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm));

            $this->DropDatabase();
        }

        public function testIsLockedOut_TestCase1():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "user1";
            $event = "login";
            $loginCount = 0;

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->IsLockedOut($ipAddr, $uid, $event));

            $this->DropDatabase();
        }

        public function testIsLockedOut_TestCase2():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "user1";
            $event = "login";
            $loginCount = 1;


            $loginManager = LoginManager::getInstance();
            $loginManager->FailedLogin($uid, $ipAddr);
            $this->AssertFalse($loginManager->IsLockedOut($ipAddr, $uid, $event));

            $this->DropDatabase();
        }

        public function testIsLockedOut_TestCase3():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "user1";
            $event = "login";
            $loginCount = 4;

            $loginManager = LoginManager::getInstance();
            for($i = 1; $i <= $loginCount; $i++){
                $loginManager->FailedLogin($uid, $ipAddr);
            }

            $this->AssertFalse($loginManager->IsLockedOut($ipAddr, $uid, $event));

            $this->DropDatabase();
        }

        public function testIsLockedOut_TestCase4():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "user1";
            $event = "login";
            $loginCount = 5;

            $loginManager = LoginManager::getInstance();
            for($i = 1; $i <= $loginCount; $i++){
                $loginManager->FailedLogin($uid, $ipAddr);
            }

            $this->AssertTrue($loginManager->IsLockedOut($ipAddr, $uid, $event));

            $this->DropDatabase();
        }

        public function testIsLockedOut_TestCase5():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = null;
            $event = "login";
            $loginCount = 1;

            $loginManager = LoginManager::getInstance();
            for($i = 1; $i <= $loginCount; $i++){
                $loginManager->FailedLogin($uid, $ipAddr);
            }

            //$this->expectException(Exception::class);
            $this->AssertFalse($loginManager->IsLockedOut($ipAddr, $uid, $event));

            $this->DropDatabase();
        }

        public function testProcessRegistration_TestCase1():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "userTwo";
            $pwd = "Password2!";

            $loginManager = LoginManager::getInstance();
            $this->AssertTrue($loginManager->ProcessRegistration($uid, $pwd, $ipAddr));
            // make sure login works with newly created credentials
            $this->AssertTrue($loginManager->ProcessLogin($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessRegistration_TestCase2():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "user2";
            $pwd = "Password2!";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->ProcessRegistration($uid, $pwd, $ipAddr));
            

            $this->DropDatabase();
        }

        public function testProcessRegistration_TestCase3():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "userTwo";
            $pwd = "PasswordTwo";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->ProcessRegistration($uid, $pwd, $ipAddr));
            

            $this->DropDatabase();
        }

        public function testProcessRegistration_TestCase4():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "";
            $pwd = "Password2!";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->ProcessRegistration($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessRegistration_TestCase5():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "userTwo";
            $pwd = "";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->ProcessRegistration($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessRegistration_TestCase6():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = null;
            $pwd = "Password2!";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->ProcessRegistration($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testProcessRegistration_TestCase7():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "userTwo";
            $pwd = null;

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->ProcessRegistration($uid, $pwd, $ipAddr));

            $this->DropDatabase();
        }

        public function testFailedRegistration_TestCase1():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "user2";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->FailedRegistration($uid,$ipAddr));

            $this->DropDatabase();
        }

        public function testFailedRegistration_TestCase2():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "user1";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->FailedRegistration($uid,$ipAddr));

            $this->DropDatabase();
        }

        public function testFailedRegistration_TestCase3():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "userTwo";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->FailedRegistration($uid,$ipAddr));

            $this->DropDatabase();
        }

        public function testFailedRegistration_TestCase4():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = "";

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->FailedRegistration($uid,$ipAddr));

            $this->DropDatabase();
        }

        public function testFailedRegistration_TestCase5():void{
            $this->SetDatabase();
            
            $ipAddr = "testIP";
            $uid = null;

            $loginManager = LoginManager::getInstance();
            $this->AssertFalse($loginManager->FailedRegistration($uid,$ipAddr));

            $this->DropDatabase();
        }
    }
?>