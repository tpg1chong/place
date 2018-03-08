<?php

class Controller {

    public $format = "html";
    public $pathName = "";


    function __construct() {
        $this->fn = new Fn();
        $this->format =  $this->_httprequestFormat();
        
        // View
        $this->view = new View();
        $this->view->format = $this->format;
    }
 
    private function _httprequestFormat() {
        $_q = 'html';
        if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) ){
            if( strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ){
                $_q = 'json';
            }
        }

        return $_q;
    }

    /**
     * 
     * @param string $name Name of the model
     * @param string $path Location of the models
     */
    public function loadModel($name, $modelPath = 'models/') {

        $path = $modelPath . $name.'_model.php';
        
        if (file_exists($path)) {
            require $modelPath .$name.'_model.php';
            
            $modelName = $name . '_Model';
            $this->model = new $modelName();
        }
        else{
            $this->model = new Model();
        }

        $this->init( $name );
        $this->handleLogin();
    }

    // return FORM Error
    protected function _getError($err) {
        $err = explode(',', rtrim($err, ','));

        $error = array();
        foreach ($err as $k) {
            $str = explode('=>', $k);
            $error[$str[0]] = $str[1];
        }

        return $error;        
    }

    public $me = null;
    public function handleLogin(){
        Session::init();

        if ( Cookie::get( COOKIE_KEY_USER ) ) {
            $me = $this->model->query('users')->get( Cookie::get( COOKIE_KEY_USER ) );
        }

        if( !empty($me) ){
            $this->me =  $me;

            if( !empty($this->me['lang']) ){
                Session::init();
                Session::set('lang', $this->me['lang']);
                
                $this->lang->set( $this->me['lang'] );
            }

            
            $this->view->setData('me', $this->me);
            Cookie::set( COOKIE_KEY_USER, $this->me['id'], time() + (3600*24));

            // 
            /* -- authorization -- */
            $this->page['auth'] = $this->model->query('system')->auth( !empty($this->me['access']) ? $this->me['access']: array() );
            $this->view->setPage('auth', $this->page['auth']);
            $this->view->setPage('me', $this->me);
        }
        else if( !empty($this->page['loggedOn']) ) {
            $this->login();
        }
    }


    // Page Error
    public function error(){
        if( !$this->model ){
            $this->loadModel('error');
        }

        if( $this->format=='json' ){

            echo json_encode(array(
                'error' => 404,
                'message' => 'Page not found'
            ));
        }
        else{
            $this->view->setPage('title', $this->lang->getCode()=='th'?'ไม่พบเพจ': 'Page not found');
            $this->view->elem('body')->addClass('page-errors');
            $this->view->render( 'error' );
        }

        
        exit;
    }

    // Page Login
    public function login() {

        Session::init();
        $attempt = Session::get('login_attempt');
        if( isset($attempt) && $attempt>=2 ){
            $this->view->setData('captcha', true);
            $this->view->js('https://www.google.com/recaptcha/api.js', true);
        }
        elseif( empty($attempt) ){
            $attempt = 0;
            Session::set('login_attempt', $attempt);
        }

        $login_mode = isset($_REQUEST['login_mode']) ? $_REQUEST['login_mode']: 'default';

        if( empty($_REQUEST['g-recaptcha-response']) && $attempt>2 ){
            $error['captcha'] = 'คุณป้อนรหัสไม่ถูกต้อง?';
        }
        if( !empty($_POST) && empty($error) ){            
            try {
                $form = new Form();

                $form   ->post('email')->val('is_empty')
                        ->post('pass')->val('is_empty');

                $form->submit();
                $post = $form->fetch();

                $id = $this->model->query('users')->login($post['email'], $post['pass']);

                if( !empty($id) ){

                    Cookie::set( COOKIE_KEY_USER, $id, time() + (3600*24));
                    Session::set('isPushedLeft', 1);

                    if( isset($attempt) ){
                       Session::clear('login_attempt');
                    }

                    $url = !empty($_REQUEST['next'])
                        ? $_REQUEST['next']
                        : $_SERVER['REQUEST_URI'];

                    header('Location: '.$url);
                }
                else{

                    if(!$this->model->query('users')->is_user($post['email'])){
                        $error['email'] = 'ชื่อผู้ใช้ไม่ถูกต้อง'; 
                    }
                    else{
                        $error['pass'] = 'รหัสผ่านไม่ถูกต้อง';
                    }
                }

                $post['pass'] = "";
                $this->view->setData('post', $post);
            } catch (Exception $e) {
                $error = $this->_getError( $e->getMessage() );
            }            
        }

        if(!empty($error)){

            if( isset($attempt) ){
                $attempt++;
                Session::set('login_attempt', $attempt);
            }

            $this->view->setData('error', $error);
        }

        $redirect = URL;
        $next = isset($_REQUEST['next']) ? $_REQUEST['next']: '';
        
        if( in_array($this->view->getPage('theme'), array('manage')) ){
            $redirect = URL.$this->view->getPage('theme');
        }
        
        if( !empty( $next) ){
            $this->view->setData('next', $next);
        }

        $this->view->setPage('title', Translate::Val('Login') . ' - ' . $this->page['title'] );
        $this->view->setPage('theme', 'login');


        /*$this->format = $this->_httprequestFormat();
        $this->view->setPage('locale', $this->lang->getCode() );*/

        /*
        $this->view->setData('redirect', $redirect);
        $this->view->setPage('name', $this->lang->getCode()=='th'?'เข้าสู่ระบบ': 'Login');
        
        $this->view->setPage('theme_options', array(
            'has_topbar' => false,
            'has_footer' => false,
            'has_menu' => false,
        ));*/

        $this->view->render( 'default' );
        exit;
    }

    /* -- init --*/
    public function init($on) {

        // Get Data System
        $this->page = array_merge(array(
            'elem' => array(),
            'data' => array(),
            'theme_options' => array()
        ), $this->model->query('system')->get() );
        $this->page['on'] = $on;


        $theme = array('name'=>'');
        // set Theme
        if( $on == 'admin' ){
            $this->page['theme'] = 'admin';
            $this->page['loggedOn'] = true;
        }

        if( empty($this->page['theme']) ){

            $this->page['theme'] = 'default';
            $this->page['theme_options'] = array('topbar'=>true, 'footer'=>true);
        }

        $this->view->page = $this->page;
        // authorization
    }



    // Permit Page
    public function setPagePermit() {

        $permit = $this->model->query('system')->permit( !empty($this->me['access']) ? $this->me['access']:array() );

        if( !empty($this->me['permission']) ){

            foreach ($permit as $key => $value) {
                
                if( !empty($this->me['permission'][ $key ]) ){
                    $permit[$key] = array_merge($value, $this->me['permission'][ $key ]);
                }
            }

        }

        // print_r($permit); die;
        
        $this->permit = $permit;
        $this->view->setData('permit', $this->permit);

        /*echo 'options page';
        print_r($this->page); die;

        # Check Permit
        if( !empty($this->page['data']['permit']) && !empty($this->page['theme_options']['logged']) ){

            $on = $this->page['on'];
            if( empty($on) ) $on = $name;

            if( empty($this->page['data']['permit'][$on][$this->page['action']]) ){

                if( $this->format=='json' ){
                    echo json_encode(array(
                        'error' => 404,
                        'message' => 'Page not found'
                    )); exit;
                }
                else{
                    $this->setPage('title', 'Page not found');
                    $name = 'error';
                }
                
            }
        }*/
    }

    // set Data Default Page
    public function setupSystem(){

        $url = isset($_GET['url']) ?$_GET['url']:'';
        $title = '';
        if( !empty($url) ){
            $url = trim($url, '/');
            $url = str_replace('-', ' ', $url);
            $title = str_replace('/', ' - ', $url);
        }

        if( empty($title) && !empty($this->system['title']) ){
            $title = $this->system['title'];
        }

        $this->view->setPage('title', $title);

        $on = str_replace(' ', '-', $url);
        if( empty($on) ) $on = 'index';

        $this->view->setPage('on', $on );

        $this->system['url'] = URL.$on;
        $keys = array('site_name', 'type', 'url', 'image', 'keywords', 'color', 'facebook_app_id');
        foreach ($keys as $key) {
            if( !empty($this->system[$key]) ){

                if( $key=='site_name' ){
                    $this->view->setPage('site', $this->system[$key] );
                }

                $this->view->setPage($key, $this->system[$key] );
            }
        }

        if( !empty($this->system['blurb']) || !empty($this->system['description']) ){

            $description  ='';
            if( !empty($this->system['blurb']) ){
                $description = $this->system['blurb'];
            }
            else{
                $description = $this->fn->q('text')->more($this->system['description']);
            }

            $this->view->setPage('description',  $description );
        }  
    
        
        $this->view->setPage('logo', IMAGES.'logo/top-logo.png' );
        if( empty($this->system['image']) ){
            $this->view->setPage( 'image', IMAGES.'logo/top-logo.png' );
        }

        if( !empty($this->system['theme']) ){
            $this->view->setPage('theme',  $this->system['theme'] );
        }
    }

}