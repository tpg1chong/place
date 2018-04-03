<?php

class Admin_Controller extends Controller
{

	public function __construct( $a ) {
        parent::__construct();

        $this->setPage( array(
            'theme' => 'admin',
            'theme_options' => array(
                'head' => true,
                'leftMenu' => true,
            ),
            'loggedOn' => true,
            'render' => !empty($a[1]) ?$a[1]:'',

            'favicon' => IMAGES.'favicon.ico'
        ) );
    }

    public function navTrigger() {
        if( $this->format!='json' ) $this->error();
        if( isset($_REQUEST['status']) ){
            Session::init();
            Session::set('isPushedLeft', $_REQUEST['status']);
        }
    }

    public function index($value='') {

        header('location: '.URL.'admin/location');
    }
    public function business() 
    {
        $this->view->setPage('on', 'business');

        $this->view->render("business/display");
    }

    /* -- account -- */
    public function account($section='settings')
    {

        if( !in_array($section, array('settings', 'authorization') )) $this->error();

        if( $section=='authorization' ){
            $this->view->setPage('on', 'authorization');
            $this->view->setData('roles', $this->model->query('users')->admin_roles());

            $users = $this->model->query('users')->find();
            $this->view->setData('data', $users['lists']);
        }

        if($section=='settings'){
            $this->view->setPage('on', 'my');
        }

        $this->view->setData('section', $section);
        $this->view->render("account/display");
    }
    public function logout()
    {
        $url = URL.'admin/';
        $this->view->setPage('theme', 'login');

        if( $this->format == 'json' ){
            $this->view->render('confirm_logout');
            exit;
        }

        if( empty($this->me) ){
            header('location:' . $url );
        }

        Session::init();
        Session::destroy();


        $url = !empty($_REQUEST['next'])
            ? $_REQUEST['next']
            : $url;

        Cookie::clear( COOKIE_KEY_ADMIN );
        Cookie::clear( 'login_role' );

        header('location:' . $url);
    }

    /* -- site -- */
    public function site($section='')
    {

        if( $section=='banner' ){
            $this->view->setData('bannersList', array());
        }


        $this->view->setPage('on', 'site_manager');
        $this->view->setData('section', $section);
        $this->view->render("site/display");
    }


    /* -- Place -- */
    public function place($id=null)
    {

        // print_r($this->model->query('place')->find()); die;
        if( !empty($id) ){
            $item = $this->model->query('place')->findById( $id );
            if( empty($item) ) $this->error();

            $this->view->setData('item', $item );

            $type = $this->model->query('property')->type->find();
            $this->view->setData('typeList', $type['items'] );

            $country = $this->model->query('location')->country->find();
            $this->view->setData('countryList', $country['items'] );

            $facilities = $this->model->query('property')->facilities->find();
            $this->view->setData('facilitiesList', $facilities['items'] );

            $this->view->setPage('on', 'place' );
            $this->view->render("place/profile/display");
        }
        else{
            if( $this->format == 'json' ){

                $this->view->setData( 'results', $this->model->query('place')->find() );
                $this->view->render("place/lists/json");
            }
            else{

                $type = $this->model->query('property')->type->find();
                $this->view->setData('typeList', $type['items'] );

                $country = $this->model->query('location')->country->find();
                $this->view->setData('countryList', $country['items'] );

                $this->view->setPage('on', 'place' );
                $this->view->render("place/lists/display");
            }
        }
    }
    /* -- property -- */
    public function property($section='available')
    {

        $this->view->setPage('on', 'property_manager');
        $this->view->setData('section', $section);

        if( in_array($section, array('room_type', 'amenities', 'offers')) )
        {
            $results = $this->model->query('property')->{$section}->find();
            $this->view->setData('dataList', $results['items'] );
		}
        $this->view->render("property/display");
    }

    /* -- location -- */
	public function location($section='place')
    {
		$this->view->setPage('on', 'location');
		$this->view->setData('section', $section);

		if( in_array($section, array('region', 'country', 'geography', 'city'))){

			$results = $this->model->query('location')->{$section}->find();
			$this->view->setData('dataList', $results['items'] );
		}

        if( in_array($section, array('category', 'type', 'facilities', 'payment_options', 'transportation'))){

            $results = $this->model->query('property')->{$section}->find();
            $this->view->setData('dataList', $results['items'] );
        }


        if($section=='create'){

            $type = $this->model->query('property')->type->find();
            $this->view->setData('typeList', $type['items'] );

            $facilities = $this->model->query('property')->facilities->find();
            $this->view->setData('facilitiesList', $facilities['items'] );

            $payment = $this->model->query('property')->payment_options->find();
            $this->view->setData('paymentList', $payment['items'] );

            $transportation = $this->model->query('property')->transportation->find();
            $this->view->setData('transportationList', $transportation['items'] );

            /*$roomType = $this->model->query('property')->room_type->find();
            $this->view->setData('roomTypeList', $roomType['items'] );*/

            $country = $this->model->query('location')->country->find();
            $this->view->setData('countryList', $country['items'] );

            $this->view->js( VIEW.'Themes/admin/assets/js/formPlacesCreate.js', 1 );
        }

		$this->view->render("location/display");
	}


    public function promotions()
    {
        $this->view->setPage('on', 'promotions');
        $this->error();
    }

    public function blog($section='published')
    {

        $this->view->setPage('on', 'blog_manager');
        $this->view->setData('section', $section);


        if( in_array($section, array('forum', 'category')) ) {
            $results = $this->model->query('blog')->{$section}->find();
            $this->view->setData('dataList', $results['items'] );
        }

        $this->view->render("blog/display");
    }


    /* -- member -- */
    public function member()
    {
        $this->view->setPage('on', 'member');
        $this->error();
    }

    /* -- inbox -- */
    public function inbox()
    {
        $this->view->setPage('on', 'inbox');
        $this->error();
    }

    public function overview()
    {
        $this->view->setPage('on', 'overview');
        $this->error();
    }


    public function partner()
    {

        if( $this->format == 'json' ){

            $this->view->setData( 'results', $this->model->query('partner')->find() );
            $this->view->render("partner/lists/json");
        }
        else{

            $this->view->setPage('on', 'partner' );
            $this->view->render("partner/lists/display");
        }
    }


    public function calendar() {

        $this->view->setPage('on', 'calendar' );
        $this->view->render("calendar/display");
    }
}
