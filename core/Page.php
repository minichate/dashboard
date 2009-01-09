<?php

class Page extends defaultPageActs {
	
	public $tables = array();
	public $pointer = null;
	public $actionspointer = null;
	public $heading = array();
	public $names = array();
	public $actions = array();
	public $showcreate = array();
	public $link = array();
	public $filter = array();
	public $pre = array();
	public $post = array();
	public $ajax = array();
	public $renderer = array();
	public $order = array();
	public $pageActions = array();
	public $useDefaultActions = true;
	public $showLink = array();
	
	public $perPage = 10;
	
	public $user = null;
	
	public function __construct() {
		$this->user =& $_SESSION['authenticated_user'];
	}
	
	public function &with( $class ) {
		$this->pointer = $class;
		return $this;
	}
	
	public function &show(Array $properties) {
		$this->tables[$this->pointer] = $properties;
		if (!isset($this->showcreate[$this->pointer])) {
			$this->showcreate[$this->pointer] = true;
		}
		return $this;
	}
	
	public function &heading( $title ) {
		$this->heading[$this->pointer] = $title;
		return $this;
	}
	
	public function &orderBy( $order ) {
		$this->order[$this->pointer] = $order;
		return $this;
	}
	
	public function &name( $name ) {
		$this->names[$this->pointer] = $name;
		return $this;
	}
	
	public function &on( $event ) {
		$this->actionspointer = $event;
		return $this;
	}
	
	public function &action( $action ) {
		$this->actions[$this->pointer][$this->actionspointer] = $action;
		return $this;
	}
	
	public function &link(Array $link) {
		$this->link[$this->pointer] = $link;
		if(!array_key_exists($this->pointer, $this->showLink)) $this->showLink(true);
		return $this;
	}
	
	public function &showLink($bool) {
		if(is_bool($bool)){
			$this->showLink[$this->pointer] = $bool;
		}
		return $this;
	}
	
	public function &filter($filter) {
		$this->filter[$this->pointer] = $filter;
		return $this;
	}
	
	public function &noAJAX() {
		$this->ajax[$this->actionspointer] = false;
		return $this;
	}
	
	public function &pageAction($action){
		$this->pageActions[$this->pointer][$action] = array();
		$this->pageActions[$this->pointer][$action]['perm'] = $action;
		$this->pageActions[$this->pointer][$action]['class'] = $action;
		$this->pageActions[$this->pointer][$action]['icon'] = '';
		$this->pageActions[$this->pointer][$action]['callback'] = '';
		$this->pageActions[$this->pointer][$action]['restrict'] = array();
		$this->pageActions[$this->pointer][$action]['show'] = true;
		return $this;
	}
	
	public function &icon($icon){
		end($this->pageActions[$this->pointer]);
		$this->pageActions[$this->pointer][key($this->pageActions[$this->pointer])]['icon'] = $icon;
		return $this;
	}
	
	public function &callback($function){
		end($this->pageActions[$this->pointer]);
		$this->pageActions[$this->pointer][key($this->pageActions[$this->pointer])]['callback'] = $function;
		return $this;
	}
	
	public function &restrict(Array $restriction){
		end($this->pageActions[$this->pointer]);
		$this->pageActions[$this->pointer][key($this->pageActions[$this->pointer])]['restrict'] = $restriction;
		return $this;
	}
	
	public function &usePerm($perm){
		end($this->pageActions[$this->pointer]);
		$this->pageActions[$this->pointer][key($this->pageActions[$this->pointer])]['perm'] = $perm;
		return $this;
	}
	
	public function &setClass($class){
		end($this->pageActions[$this->pointer]);
		$this->pageActions[$this->pointer][key($this->pageActions[$this->pointer])]['class'] = $class;
		return $this;
	}
	
	public function &toggleShow(){
		end($this->pageActions[$this->pointer]);
		$this->pageActions[$this->pointer][key($this->pageActions[$this->pointer])]['show'] = !$this->pageActions[$this->pointer][key($this->pageActions[$this->pointer])]['show'];
		return $this;
	}
	
	public function &defaultPageActions($bool){
		if(is_bool($bool)){
			$this->useDefaultActions = $bool;
		}
		return $this;
	}
	
	public function &pre($html) {
		$this->pre[$this->pointer] = $html;
		return $this;
	}
	
	public function &post($html) {
		$this->post[$this->pointer] = $html;
		return $this;
	}
	
	public function &showCreate( $bool ) {
		$this->showcreate[$this->pointer] = $bool;
		return $this;
	}
	
	public function error($type) {
		return 'error '.$type;
	}
	
	public function &renderer($smarty, $template) {
		if (isset($this->actionspointer)) {
			$this->renderer[$this->pointer][$this->actionspointer] = array($smarty, $template);
		} else {
			$this->renderer[$this->pointer] = array($smarty, $template);
		}
		return $this;
	}
	
	public function getName() {
		if (isset($this->names[$this->pointer])) return $this->names[$this->pointer];
		return $this->pointer;
	}
	
	public function restricted($action, $item){
		if(key_exists('restrict', $this->pageActions[$this->pointer][$action])){
			foreach($this->tables[$this->pointer] as $column){
				if(is_array($column)){
					$column = $column[1][1];
				}
				if(key_exists($column, $this->pageActions[$this->pointer][$action]['restrict'])){
					$value = call_user_func(array($item, 'get'), $column);
					if($this->pageActions[$this->pointer][$action]['restrict'][$column] == $value) return true;
					else if(is_array($this->pageActions[$this->pointer][$action]['restrict'][$column]) && in_array($value, $this->pageActions[$this->pointer][$action]['restrict'][$column])) return true;
				}
			}
		}
		return false;
	}
	
	public function catchActions() {
		if (!isset($_REQUEST['section']) && !isset($_REQUEST['action'])) return; 
		
		if (isset($_REQUEST['section'])) {
			$this->pointer = $_REQUEST['section'];
			$type = $_REQUEST['section'];
			if($this->useDefaultActions){
				$this->initDefaultActs();
			}
		} else {
			$type = $this->pointer;
		}
		
		if (isset($this->actions[$this->pointer][@$_REQUEST['action']])) {
			$this->pointer = $this->actions[$this->pointer][$_REQUEST['action']];
			if($this->useDefaultActions){
				$this->initDefaultActs();
			}
			return;
		}
		
		$received = null;
		$idField = call_user_func(array($type, 'quickformPrefix'));
		$i = call_user_func(array('DBRow', 'make'), @$_REQUEST[$idField . 'id'], $type);

		if(array_key_exists('action', $_REQUEST) && array_key_exists($_REQUEST['action'], $this->pageActions[$this->pointer])){
			if($this->user->hasPerm($this->pointer, $this->pageActions[$this->pointer][$_REQUEST['action']]['perm'])){
				if(method_exists($i, $this->pageActions[$this->pointer][$_REQUEST['action']]['callback'])){
					$received = call_user_func(array($i, $this->pageActions[$this->pointer][$_REQUEST['action']]['callback']));
				} else if(method_exists($this, 'default_' . $this->pageActions[$this->pointer][$_REQUEST['action']]['callback'])){
					$received = call_user_func(array($this, 'default_' . $this->pageActions[$this->pointer][$_REQUEST['action']]['callback']), $i, $idField);
				}
			}
		}
		if(is_string($received) && !empty($received)) return $received;
		return false;
	}
	
	public function render() {
		if($this->useDefaultActions){
			$this->initDefaultActs();
		}
		
		$type = isset($_REQUEST['X-ResultType']) ? $_REQUEST['X-ResultType'] : 'html';

		if ($r = $this->catchActions()) return $r;
		
		$html = '';
		$where = null;
		if (isset($this->link[$this->pointer])) {
			$where = ' where ';
			$prefix = call_user_func(array($this->link[$this->pointer][1][0], 'quickformPrefix'));
			if (isset($_REQUEST[$prefix . $this->link[$this->pointer][1][1]])) {
				$where .= $this->link[$this->pointer][0] . '=' . $_REQUEST[$prefix . $this->link[$this->pointer][1][1]];
			} else if (!isset($_REQUEST[call_user_func(array($this->pointer, 'quickformPrefix')) . 'id'])) {
				$prefix = call_user_func(array($this->pointer, 'quickformPrefix'));
				$where .= $this->link[$this->pointer][0] . '=' . $_REQUEST[$prefix . $this->link[$this->pointer][0]];
			} else {
				$prefix = call_user_func(array($this->pointer, 'quickformPrefix'));
				$n = DBRow::make($_REQUEST[$prefix . 'id'], $this->pointer);
				$where .= $this->link[$this->pointer][0] . '=' . $n->get($this->link[$this->pointer][0]);
			}
			
		}
		if (isset($this->filter[$this->pointer])) {
			if (!is_array($this->filter[$this->pointer])) {
				$where .= $this->filter[$this->pointer];
			}
		}
		
		
		if (isset($this->order[$this->pointer])) {
			$where .= ' order by ' . $this->order[$this->pointer];
		}
		
		$this->perPage = isset($_REQUEST['X-DataLimit']) ? $_REQUEST['X-DataLimit'] : $this->perPage;
		$sql = 'select count(id) as count from ' . (call_user_func(array($this->pointer, 'createTable'))->name()) . ' ' . $where;
			$r = Database::singleton()->query_fetch($sql);
			
			$currentPage = @$_REQUEST['pageID'];
			
			require_once('Pager/Pager.php');
			$pagerOptions = array(
			    'mode'     => 'Sliding',
			    'delta'    => 4,
			    'perPage'  => $this->perPage,
				'append'   => true,  //don't append the GET parameters to the url
		  	  	'fileName'     => '/admin/' . $_REQUEST['module'] . "&section=" . $this->pointer . "&pageID=%d",
		    	'path' => '/admin/Contacts',
				'totalItems' => $r['count']
			);
			$pager =& Pager::factory($pagerOptions);
			
			list($from, $to) = $pager->getOffsetByPageId();
			$where .= ' limit ' . ($from - 1) . ', ' . ($this->perPage);
			$items = call_user_func(array($this->pointer, 'getAll'), $where);
		
		switch ($type) {
		case 'html':
		default:
		if (isset($this->link[$this->pointer]) && $this->showLink[$this->pointer]) {
			$linked = $this->link[$this->pointer][1][0];
			$prefix = call_user_func(array($linked, 'quickformPrefix'));
			
			if ($id = @$_REQUEST[$prefix . $this->link[$this->pointer][1][1]]) {
				$ownprefix = call_user_func(array($this->pointer, 'quickformPrefix'));
				$add = "&amp;" . $ownprefix . $this->link[$this->pointer][0] . '=' . $id;
			} else if ($items[0]) {
				$ownprefix = call_user_func(array($this->pointer, 'quickformPrefix'));
				$add = "&amp;" . $ownprefix . $this->link[$this->pointer][0] . '=' . $items[0]->get($this->link[$this->pointer][0]);
			}
		}
		
		if (count($this->heading)) {
			$html .= '<div id="subnav">';
			foreach ($this->heading as $key => $head) {
				if ($this->user->hasPerm($key, 'view'))
				$html .= ' <a href="/admin/' . $_REQUEST['module'] . '&amp;section=' . $key . @$add . '">' . $head . '</a> | ';
			}
			$html = rtrim($html, ' |');
			$html .= '</div>';
		}
		
		if (isset($this->link[$this->pointer]) && $this->showLink[$this->pointer]) {
			$class = $this->link[$this->pointer][1][0];
			$prefix = call_user_func(array($class, 'quickformPrefix'));
			if (isset($_REQUEST[$prefix . 'id'])) {
				$r = $this->catchActions();
				$i = DBRow::make($_REQUEST[$prefix . 'id'], $class);
				$f = $i->getAddEditForm('/admin/' . $_REQUEST['module']);
				// BUG:  CHRIS THINKS THIS WAS INSERTED TO MASK A BUG IN DBROW, PERHAPS...  WE SHALL SEE.
				// $i->__construct($i->getId());
				// $f = $i->getAddEditForm('/admin/' . $_REQUEST['module']);
				$html .= $f->display();
			}
		}
		if (!$this->user->hasPerm($this->pointer, 'view')) {
			return $html . $this->error('view');
		}
		
		if (isset($this->pre[$this->pointer]) || count($items) == 0) {
			$html .= '<div class="roundcont">

				   <div class="roundtop">
					 <img src="/images/admin/noAsset_tl.png" alt="" 
					 width="20" height="20" class="corner" 
					 style="display: none" />
				   </div>';
				
			
			if (count($items) == 0) {
				if(substr($this->getName(), 0, -1) == 's') $pfix = 'es';
				else $pfix = 's';
				$html .= '<p>No ' . $this->getName() . $pfix . ' Created.';
				if ($this->user->hasPerm($this->pointer, $this->pageActions[$this->pointer]['add']['perm'])) {
					$html .= ' Would you like to <a href="/admin/' . $_REQUEST['module'] . '&amp;section=' . $this->pointer . '&amp;action=add' . @$add . '">make one</a>?</p>';
				}
			}
			if (count($items) == 0 && (isset($this->pre[$this->pointer]))) {
				$html .= "<br /><br />";
			}
			if (isset($this->pre[$this->pointer])) {
				$html .= '<div class="interior">' . @$this->pre[$this->pointer] . '</div>';
			}
			$html .= '<div class="roundbottom">
					 <img src="/images/admin/noAsset_bl.png" alt="" 
					 width="20" height="20" class="corner" 
					 style="display: none" />
				
				   </div>

				</div>';
			
			if (count($items) == 0) {
				return $html;
			}
		}
		
		if (isset($this->showcreate[$this->pointer]) && $this->showcreate[$this->pointer] && $this->user->hasPerm($this->pointer, $this->pageActions[$this->pointer]['add']['perm'])) {
			$html .= '<div id="header">
				<ul id="primary">
					<li><a href="/admin/' . $_REQUEST['module'] . '&amp;section=' . $this->pointer . '&amp;action=add' . @$add . '" title="Create ' . $this->getName() .'">Create ' . $this->getName() . '</a></li>
				</ul></div>';
			$html .= '<div style="float: left; width: 300px;">' . $pager->links . '</div>';
		} else {
			$html .= '<div style="float: left; width: 300px;">' . $pager->links . '</div>';
			$html .= '<br />';
		}	
		
		$html .= '<table border="0" cellspacing="0" cellpadding="0" class="adminList">';
		$html .= '<tbody>';
		$html .= '<tr>';
		foreach ($this->tables[$this->pointer] as $key => $name) {
			$html .= '<th valign="middle">' . $key . '</th>';
		}
		$insertTd = false;
		foreach($this->pageActions[$this->pointer] as $action => $data){
			if ($this->user->hasPerm($this->pointer, $data['perm'])){
				$html .= '<th valign="middle">Actions</th>';
				$insertTd = true;
				break;
			}
		}
		$html .= '</tr>';
		foreach ($items as $key => $item) {
			$html .= '<tr class="';
			if ($key & 1) {
				$html .= 'row2';
			} else {
				$html .= 'row1';
			}
			$html .= '">';
			foreach ($this->tables[$this->pointer] as $key => $name) {
				$html .= '<td>';
				if (!is_array($name)) {
					$html .= $item->table()->column($name)->__toString($item,$name);
				} else {
					$tmp = DBRow::make($item->table()->column($name[0])->__toString($item,$name[0]),
									   $name[1][0]);
					for ($i = 1; $i < count($name[1]); $i++) {
						$html .= call_user_func(array($tmp, $name[1][$i])) . ' ';
					}
				}
				$html .= '</td>';
			}
			if($insertTd) $html .= '<td>';
			foreach($this->pageActions[$this->pointer] as $name => $data){
				if($this->user->hasPerm($this->pointer, $this->pageActions[$this->pointer][$name]['perm']) && !$this->restricted($name, $item) && $this->pageActions[$this->pointer][$name]['show']){
					$html .= '<form action="/admin/' . $_REQUEST['module'] . '" method="post" style="float: left;"';
				
					if (!isset($this->ajax[$name]) || $this->ajax[$name] == true) {
						$html .= ' class="norexui_'.$data['class'].'"';
					}
					
					$html .= '>
							<input type="hidden" name="section" value="' . get_class($item) . '" />
							<input type="hidden" name="action" value="' . $name . '" />
							<input type="hidden" name="' . $item->quickformPrefix() . 'id" value="' . $item->get('id') . '" />
							<input type="image" src="' . $data['icon'] . '" />
						</form>';
				}
			}
			if($insertTd) $html .= '</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		
		$html .= '</table>';
		
		if (isset($this->post[$this->pointer])) {
			$html .= $this->post[$this->pointer];
		}
		return $html;
		break;
		
		case 'json':
			$tmp = array();
			foreach ($items as $item) {
				$tmp[] = $item->values();
			}
			
			header('Content-Type: text/javascript');
			echo json_encode($tmp);
			die();
		}
	}
	
}

?>
