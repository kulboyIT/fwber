<?php

    session_start();

    require_once("_init.php");
	
	if($_SERVER["REQUEST_METHOD"] != "POST"){header('Location: '.getSiteURL());exit();}

    require_once("_profileVars.php");
    require_once("_dbSettings.php");
    require_once("_globals.php");

    //first make sure we are a legit user.
    if(deleteCookiesIfInvalid()==false){header('Location: '.getSiteURL());exit();}//full auth for actions

    goHomeIfCookieNotSet();

    $db = mysqli_connect($dburl,$dbuser,$dbpass);
    if(!$db)exit(mysqli_connect_error());

    //make sure we've got an action.
    if(!isset($_POST['myPass'])||empty($_POST['myPass']))exit('myPass'); else $myPass= mysqli_escape_string($db,$_POST['myPass']);
    if(!isset($_POST['myPassAgain'])||empty($_POST['myPassAgain']))exit('myPassAgain'); else $myPassAgain= mysqli_escape_string($db,$_POST['myPassAgain']);
    if(!isset($_POST['goodbyeCheck'])||empty($_POST['goodbyeCheck']))exit('goodbyeCheck'); else $goodbyeCheck= mysqli_escape_string($db,$_POST['goodbyeCheck']);

    if($myPass!=$myPassAgain)exit("Passwords don't match");
    if($goodbyeCheck!="goodbye")exit("Didn't say goodbye.");

    //authenticate old pass
    $email = mysqli_real_escape_string($db,$_SESSION["email"]);

    $dbquerystring = sprintf("SELECT passwordHash, dateJoined, firstBasePics, allTheWayPics FROM ".$dbname.".users WHERE email='%s'",$email);
    $dbquery = mysqli_query($db, $dbquerystring);
    $dbresults = mysqli_fetch_array($dbquery);
    mysqli_free_result($dbquery);

    $message = "";

    if(
        $dbresults==null
        ||$dbresults['passwordHash']==null
        ||getSaltedPassword($myPass,$dbresults['dateJoined'])!=$dbresults['passwordHash']
    )
    {
        $message = "Password was wrong.";
    }

    if($message=="")
    {
        //connect to database, find any firstbase pictures for me.
        //delete them in the db too.
        $firstBasePics=explode(",",trim(trim($dbresults['firstBasePics']),","));
        $allTheWayPics=explode(",",trim(trim($dbresults['allTheWayPics']),","));

        //delete pics
        foreach($firstBasePics as &$s)
        {
            if($s=="")continue;

            unlink("./fwberImageStore/".$s);
            unlink("./fwberImageStore/".$s."_b");

            $s = "";
        }

        foreach($allTheWayPics as &$s)
        {
            if($s=="")continue;

            unlink("./fwberImageStore/".$s);
            unlink("./fwberImageStore/".$s."_b");

            $s = "";
        }

        //delete pics in database
        $dbquerystring =
        sprintf("UPDATE ".$dbname.".users SET firstBasePics = '%s',allTheWayPics = '%s' WHERE email='%s'",
        trim(trim(implode(",",$firstBasePics)),","),
        trim(trim(implode(",",$allTheWayPics)),","),
        $email
        );
        if(!mysqli_query($db,$dbquerystring))exit("didn't work");

        //remove user from database
        $dbquerystring =
        sprintf("DELETE FROM ".$dbname.".users WHERE email='%s'",
        $email
        );
        if(!mysqli_query($db,$dbquerystring))exit("didn't work");

        //delete cookies
        setcookie("email","",time()-1000,'/',".".getSiteDomain());
        setcookie("token","",time()-1000,'/',".".getSiteDomain());

        session_destroy();

        mysqli_close($db);

        $message = "Your account has been deleted.";

    }

    ?>
<!doctype html>
<html lang="en">
<head>
    <title><?php require_once("_names.php"); echo getSiteName(); ?> - Delete Account<?php require_once("_names.php"); echo getTitleTagline(); ?></title>
	<?php include("head.php");?>
</head>
<body class="d-flex flex-column h-100">
	<?php include("h.php");?>
	<div id="mainbody" align="center"> 
		<br>
        <br>
        <br>
		<div style="font-size:14px;">
			<?php echo $message; ?>
		</div>
	</div>
    <?php include("f.php");?>
</body>
</html>