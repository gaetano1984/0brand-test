<?php 

	namespace Controller;

	class NodeTree{

		/**
			@var $db connessione al db
		*/
		public $db;

		/**
			@var $response array contenente la risposta (da convertire in JSON) dello script
		*/
		public $response = [];

		/**
			Costruttore della classe

			Si occupa di lanciare il metodo che legge il file di configurazione e inizializza la connessione al DB
		*/
		public function __construct(){
			$this->setup();
		}

		/**
			Inizializzazione script 

			leggo i valori di configurazione e inizializzo la configurazione del database
		*/
		public function setup(){
			global $config;

			$hostname = $config['hostname']; //"127.0.0.1:8889";
			$dbname = $config['dbname'];
			$user = $config['user'];
			$pass = $config['pass'];
			$this->db = new \PDO ("mysql:host=$hostname;dbname=$dbname", $user, $pass);
		}

		/**
			Ricerca dei dati

			Questo metodo si occupa di creare le statistiche per i filtri richiesti

			@param $obj oggetto contenente i dati ricevuti dalla richiesta $_GET
		*/
		public function search($obj){

			//controllo che l'idNode passato in GET sia esistente nel DB, se non esiste lancio un eccezione
			$sql_check = "select * from node_tree where idNode=?";
			$stm_check = $this->db->prepare($sql_check);
			$stm_check->execute([$obj['node_id']]);
			$res_check = $stm_check->fetchAll();
			if(count($res_check)==0){
				$this->response = ['error' => IDNODE_NOT_VALID];
				return 0;
			}

			//query dei conteggi, individuo i nodi figli di quello specificato e ne conto i relativi figli
			$sql = '
				select b.idNode, n.NodeName, count(c.idNode) as tot
				from node_tree a
				join node_tree b
					on (b.ileft between a.ileft and a.iright) and (b.iright between a.ileft and a.iright)
				join node_tree c
					on (c.ileft between b.ileft and b.iright) and (c.iright between b.ileft and b.iright)	
				join node_tree_names n
					on b.idNode=n.idNode
				where a.idNode=:idNode
				and n.language=:language
				group by b.idNode, n.NodeName
			';
			
			//nel caso passassi anche il parametro search_keyword, filtro ulteriormente i nodi figli
			if(array_key_exists('search_keyword', $obj) && $obj['search_keyword']!=""){
				$sql .= "and NodeName like :nodeName";
			}

			//faccio il bind dei parametri nella query da ciò che ricevo in GET
			$stm = $this->db->prepare($sql);
			$stm->bindValue('idNode', $obj['node_id']);
			$stm->bindValue('language', $obj['language']);
			if(array_key_exists('search_keyword', $obj) && $obj['search_keyword']!=""){
				$stm->bindValue('nodeName', $obj['search_keyword']);
			}

			//eseguo la query, in caso di errore fermo tutto
			$stm->execute() or die(var_dump($stm->errorInfo()));
			$res = $stm->fetchAll();

			//se ottengo dei record ma vogio far vedere un numero di risultati maggiore di quelli disponibili nella query
			//lancio l'errore
			if(count($res)>0 && intval($obj['page_size'])>count($res)){
				$page_size_not_valid = PAGE_SIZE_NOT_VALID;
				$this->response = ['error' => $page_size_not_valid];
				return 0;
			}

			//nel caso passassi questi parametri li uso per estrarre solo la porzione desiderata del risultato
			$start = $obj['page_num']*$obj['page_size'];
			$end = $start+$obj['page_size'] > count($res) ? count($res) : $start+$obj['page_size'];

			$res = array_slice($res, $start, $obj['page_size']);

			//calcolo l'eventuale numero di pagina precedente
			$prev_page = $obj['page_num']>0 ? $obj['page_num']-1 : 0;
			if($prev_page>=0){
				$this->response['prev_page'] = $prev_page;
			}
			
			//calcolo l'eventuale numero di pagina successivo
			if(count($res)>intval($obj['page_size'])){
				$this->response['next_page'] = $obj['page_num']+1;
			}
			
			//inizializzo l'array dei nodi
			if(!array_key_exists('nodes', $this->response)){
				$this->response['nodes']=[];
			}
			//riempio l'array con i risultati della query
			foreach ($res as $k => $row) {
				$arr = [
					'node_id' => $row['idNode']
					,'name' => $row['NodeName']
					,'children_count' => $row['tot']
				];
				array_push($this->response['nodes'], $arr);
			}	
		}

		/**
			Validazione parametri

			Questo metodo si occupa di validare i parametri ottenuti da web
			i parametri node_id e language sono obbligatori
			i parametri page_num e page_size sono opzionali

			@method validation
			@param $obj 
		*/
		public function validation($obj){
			if(!array_key_exists('node_id', $obj) || $obj['node_id']==""){
				$this->response = ['error' =>  MISS_MAND_PARAM];
				return 0;
			}
			if(!array_key_exists('language', $obj) || $obj['language']==""){
				$this->response = ['error' => MISS_MAND_PARAM];
				return 0;
			}
			if($obj['page_num']!=0 && !intval($obj['page_num'])){
				$this->response = ['error' => PAGE_NUM_NOT_VALID];
				return 0;
			}
			if($obj['page_size']!=0 && !intval($obj['page_size'])){
				$this->response = ['error' => PAGE_SIZE_NOT_VALID];
				return 0;
			}
			return 1;
		}

		/**
			restituzione dell'esito

			Questo metodo viene invocato alla fine di tutte le operazioni, contiene l'esito finale dell'interrogazione sul DB

			@method getResponse
			@return string
		*/
		public function getResponse(){
			return json_encode($this->response);
		}
	}

 ?>