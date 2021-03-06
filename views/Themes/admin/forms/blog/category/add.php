<?php

# title
$title = 'Property Type';

if( !empty($this->item) ){
    $arr['title']= $title;
    $arr['hiddenInput'][] = array('name'=>'id','value'=>$this->item['id']);
}
else{
    $arr['title']= $title;
}


$form = new Form();
$form = $form->create()
	// set From
	->elem('div')
	->addClass('form-insert');

$form   ->field("cry_forum_id")
        ->label( Translate::Val('กลุ่มข่าว').'*' )
        ->autocomplete('off')
        ->addClass('inputtext')
        ->select( $this->forumsList )
        ->value( !empty($this->item['forum_id'])? $this->item['forum_id']:'' );

$form   ->field("cry_name")
        ->label( Translate::Val('ประเภทข่าว').'*' )
        ->autocomplete('off')
        ->addClass('inputtext')
        ->attr('autoselect', 1)
        ->placeholder('')
        ->value( !empty($this->item['name'])? $this->item['name']:'' );

# set form
$arr['form'] = '<form class="js-submit-form" method="post" action="'.URL. 'blog/save/category/"></form>';

# body
$arr['body'] = $form->html();

# fotter: button
$arr['button'] = '<button type="submit" class="btn btn-primary btn-submit"><span class="btn-text">Save</span></button>';
$arr['bottom_msg'] = '<a class="btn" data-action="close"><span class="btn-text">Cancel</span></a>';

echo json_encode($arr);