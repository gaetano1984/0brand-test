<?php 

	include('lang/error.php');

	//importo i files necessari
	include('config/config.php');	
	include('Controller/NodeTree.class.php');

	//includo il namespace della classe da usare
	use Controller\NodeTree;

	$node_id = $_GET['node_id']; //obbligatorio
	$language = $_GET['language']; //obbligatorio
	$search_keyword = $_GET['search_keyword'];
	$page_num = $_GET['page_num'];
	$page_size = $_GET['page_size'];	

	//preparo l'oggetto con i filtri
	$obj = [
		'node_id' => $node_id
		,'language' => $language
		,'search_keyword' => "%".$search_keyword."%"
		,'page_num' => $page_num
		,'page_size' => $page_size
	];


	$tree = new NodeTree(); //inizializzo la classe
	$valid = $tree->validation($obj); //valido i dati
	if($valid){
		$res = $tree->search($obj); //se passo la validazione cerco i nodi in base ai filtri
	}
	echo $tree->getResponse(); //restituisco la risposta (negativa/positiva)

 ?>