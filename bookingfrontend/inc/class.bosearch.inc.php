<?php
	phpgw::import_class('booking.bocommon');
	
	class bookingfrontend_bosearch extends booking_bocommon
	{
		function __construct()
		{
			parent::__construct();
			$this->sobuilding = CreateObject('booking.sobuilding');
			$this->soorganization = CreateObject('booking.soorganization');
			$this->soresource = CreateObject('booking.soresource');
		}
		
		function search($searchterm)
		{
			$type = phpgw::get_var('type', 'GET');
            $bui_result = $org_result = $res_result = array();

            if (!$type || $type == "building") {
                $bui_result = $this->sobuilding->read(array("query"=>$searchterm, "filters" => array("active" => "1")));
                foreach($bui_result['results'] as &$bui)
                {
                    $bui['type'] = "building";
                    $bui['link'] = $GLOBALS['phpgw']->link('/bookingfrontend/', array('menuaction' => 'bookingfrontend.uibuilding.show', 'id' => $bui['id']));
                    $bui['img_container'] = "building-" . $bui['id'];
                    $bui['img_url'] = $GLOBALS['phpgw']->link('/bookingfrontend/', array('menuaction' => 'bookingfrontend.uidocument_building.index_images', 'filter_owner_id' => $bui['id'], 'phpgw_return_as' => 'json', 'results' => '1'));
					if ( trim($bui['homepage']) != '' && !preg_match("/^http|https:\/\//", trim($bui['homepage'])) )
					{
						$bui['homepage'] = 'http://'.$bui['homepage'];
					}
                }
            }
            if (!$type || $type == "organization") {
                $org_result = $this->soorganization->read(array("query"=>$searchterm, "filters" => array("active" => "1")));
                foreach($org_result['results'] as &$org)
                {
                    $org['type'] = "organization";
                    $org['description'] = nl2br(strip_tags($org['description']));
                    $org['link'] = $GLOBALS['phpgw']->link('/bookingfrontend/', array('menuaction' => 'bookingfrontend.uiorganization.show', 'id' => $org['id']));
					if ( trim($org['homepage']) != '' && !preg_match("/^http|https:\/\//", trim($org['homepage'])) )
					{
						$org['homepage'] = 'http://'.$org['homepage'];
					}
                }
            }
            if(!$type || $type == "resource") {
                $res_result = $this->soresource->read(array("query"=>$searchterm, "filters" => array("active" => "1")));
                foreach($res_result['results'] as &$res)
                {
                    $res['name'] = $res['building_name']. ' / ' . $res['name'];
                    $res['type'] = "resource";
                    $res['link'] = $GLOBALS['phpgw']->link('/bookingfrontend/', array('menuaction' => 'bookingfrontend.uiresource.show', 'id' => $res['id']));
                    $res['img_container'] = "resource-" . $res['id'];
                    $res['img_url'] = $GLOBALS['phpgw']->link('/bookingfrontend/', array('menuaction' => 'bookingfrontend.uidocument_resource.index_images', 'filter_owner_id' => $res['id'], 'phpgw_return_as' => 'json', 'results' => '1'));
                }
            }
			$final_array = array_merge_recursive($bui_result, $org_result, $res_result);
			$final_array['total_records_sum']	=	array_sum((array)$final_array['total_records']);
			
			return $final_array;
		}
	}
