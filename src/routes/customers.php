<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$app = new \Slim\App(["setings" => $config]);

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});
// Container

// Authentication method for
$authenticate = function($request, $response, $next) {
	$header = $request->getHeaders();
	$result = array();

	if ($request->hasHeader('HTTP_AUTHORIZATION')) {
		try {
			$header = $request->getHeaders();
			//$header = substr($header['HTTP_AUTHORIZATION'][0],7);
			$secretKey = base64_decode(getenv("JWT_SECRET"));
			/// Here we will transform this array into JWT:
			$DecodedDataArray = JWT::decode(substr($header['HTTP_AUTHORIZATION'][0],7), $secretKey, array(getenv("ALGORITHM")));

			$result['error'] = false;
			$result['data'] = json_encode($DecodedDataArray);

			//echo  "{'status' : 'success' ,'data':".$result['data']." }";

			//echo $result['data'];
			//$GLOBALS['user'] = $DecodedDataArray->data;//.['data'];

			//$userdata = (array) $DecodedDataArray;
			//$GLOBALS['user'] = $userdata['data'];



			$response = $next($request, $response);

			return $response;

		} catch (Exception $e) {
			return $response->withStatus(401)->write('Unauthorized. Wrong Token.');
		}
	} else {

			return $response->withStatus(401)->write('Unauthorized. Wrong missing.');
		}
};

// Get all customers
$app->get('/api/customers', function(Request $request, Response $response)
{
	$sql = "SELECT * FROM customers";

	try {
		$db = new db();
		$db = $db->connect();

		$stmt = $db->query($sql);
		$customers = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		return json_encode($customers);
	} catch (Exception $e) {
		echo '{"error" : {"text" : '.$e->getMessage().'}';
		
	}
})->add($authenticate);


// Login for a user
$app->post('/api/login', function(Request $request, Response $response)
{	
	$email = $request->getParam('email');
	$password = $request->getParam('password');
	$sql = "SELECT password_hash FROM tbl_users WHERE email = :email LIMIT 1";

	try {
		$db = new db();
		$db = $db->connect();
		$stmt = $db->prepare($sql);
		$stmt->bindParam(':email', $email);
		$stmt->execute();
		$row_login = $stmt->fetch();
		//$db = null;
		$db_psw = $row_login['password_hash'];


		if (password_verify($password, $db_psw)) {
			$data = "SELECT name, email, date_created FROM tbl_users WHERE email = :email LIMIT 1";
			$stmt = $db->prepare($data);
			$stmt->bindParam(':email', $email);
			$stmt->execute();
			$row_userdata = $stmt->fetch();
			$name = $row_userdata['name'];
			$email = $row_userdata['email'];
			$db = null;



			// Token generation fron firebase JWT
			$tokenId    = base64_encode(mcrypt_create_iv(32));
            $issuedAt   = time();
            $notBefore  = $issuedAt;  //Adding 10 seconds
            $expire     = $notBefore + 72000; // Adding 60 seconds
            $serverName = 'http://slimapp/api/'; /// set your domain name 

				
            /*
             * Create the token as an array
             */
            $data = [
	                'iat'  => $issuedAt,         // Issued at: time when the token was generated
	                'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
	                'iss'  => $serverName,       // Issuer
	                'nbf'  => $notBefore,        // Not before
	                'exp'  => $expire,           // Expire
	                'data' => [                  // Data related to the logged user you can set your required data
			    			//'id'   => 1, // id from the users table
			    			'name' => $name,
			     			'email' => $email //  name
	                          ]
            		];
			$secretKey = base64_decode(getenv("JWT_SECRET"));
			/// Here we will transform this array into JWT:
			$jwt = JWT::encode($data, $secretKey, getenv("ALGORITHM")); 

         	$encodedArray = ['jwt' => $jwt];

			$result = array();
			$result['error'] = false;
			$result['name'] = $row_userdata['name'];
			$result['email'] = $row_userdata['email'];
			$result['createdAt'] = $row_userdata['date_created'];
			$result['token'] = $jwt;
		} else {
			$result['error'] = true;
			$result['message'] = 'Wrong password!';
		}

		if (!$row_login) {
			$result['error'] = true;
			$result['message'] = 'Wrong Email address !';
		}
		return $this->response->withJson($result);
		//return $this->response->withJson($result);

	} catch (Exception $e) {
		echo '{"error" : {"text" : '.$e->getMessage().'}';
		
	}
});


// Get a single customer
$app->get('/api/customer/{id}', function(Request $request, Response $response)
{	
	$id = $request->getAttribute('id');
	$sql = "SELECT * FROM customers WHERE id = $id";

	try {
		$db = new db();
		$db = $db->connect();

		$stmt = $db->query($sql);
		$customer = $stmt->fetch(PDO::FETCH_OBJ);
		$db = null;
		echo json_encode($customer);
		//echo "Not exist";
	} catch (Exception $e) {
		echo '{"error" : {"text" : '.$e->getMessage().'}';
		
	}
})->add($authenticate);

// Insert a new customer
$app->post('/api/customer/add', function(Request $request, Response $response)
{	
	$first_name = $request->getParam('first_name');
	$last_name = $request->getParam('last_name');
	$phone = $request->getParam('phone');
	$email = $request->getParam('email');
	$address = $request->getParam('address');
	$city = $request->getParam('city');
	$state = $request->getParam('state');
	$sql = "INSERT INTO customers (first_name, last_name, phone, email, address, city, state) VALUES (:first_name, :last_name, :phone, :email, :address, :city, :state)";

	try {
		$db = new db();
		$db = $db->connect();

		$stmt = $db->prepare($sql);

		$stmt->bindParam(':first_name', $first_name);
		$stmt->bindParam(':last_name', $last_name);
		$stmt->bindParam(':phone', $phone);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':address', $address);
		$stmt->bindParam(':city', $city);
		$stmt->bindParam(':state', $state);


		$stmt->execute();

		echo '{"notice": {"text": "Customer Added"}';
	} catch (Exception $e) {
		echo '{"error" : {"text" : '.$e->getMessage().'}';
		
	}
})->add($authenticate);
/*
{
	"first_name":"Brad",
    "last_name":"Hussy",
    "phone":"880-171-454-658",
    "email":"bradhussy@gmail.com",
    "address":"a4/99, Calf",
    "city":"Bogra",
    "state":"Rajshahi"
}
*/


// Edit a customr info

$app->put('/api/customer/update/{id}', function(Request $request, Response $response)
{	
	$id = $request->getAttribute('id');
	$first_name = $request->getParam('first_name');
	$last_name = $request->getParam('last_name');
	$phone = $request->getParam('phone');
	$email = $request->getParam('email');
	$address = $request->getParam('address');
	$city = $request->getParam('city');
	$state = $request->getParam('state');
	$sql = "UPDATE customers SET
			first_name = :first_name,
			last_name = :last_name,
			phone = :phone,
			email = :email,
			address = :address,
			city = :city,
			state = :state
		WHERE id = $id";

	try {
		$db = new db();
		$db = $db->connect();

		$stmt = $db->prepare($sql);

		$stmt->bindParam(':first_name', $first_name);
		$stmt->bindParam(':last_name', $last_name);
		$stmt->bindParam(':phone', $phone);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':address', $address);
		$stmt->bindParam(':city', $city);
		$stmt->bindParam(':state', $state);


		$stmt->execute();

		echo '{"notice": {"text": "Customer Updated"}';

	} catch (Exception $e) {
		echo '{"error" : {"text" : '.$e->getMessage().'}';
		
	}
});

// Delete a customer info
$app->delete('/api/customer/delete/{id}', function(Request $request, Response $response)
{	
	$id = $request->getAttribute('id');

	$sql = "DELETE FROM customers WHERE id = $id";

	try {
		$db = new db();
		$db = $db->connect();

		$stmt = $db->prepare($sql);

		$stmt->execute();
		$db = null;

		echo '{"notice": {"text": "Customer Deleted"}}';
	} catch (Exception $e) {
		echo '{"error" : {"text" : '.$e->getMessage().'}';
		
	}
})->add($authenticate);


$app->get('/api/{userID}', function($request, $response, $args){
	$response->getBody()->write("getData....");
	$response->getBody()->write($args['userID']);
	//$user= json_decode($GLOBALS['user']);
	//$user= $userdata;
	//var_dump(json_encode($user));

	return $response;
})->add($authenticate);

