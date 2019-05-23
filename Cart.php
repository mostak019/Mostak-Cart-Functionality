<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart extends CI_Controller{
	
	public function __construct()
	{
		parent::__construct();
		
		//load model
		$this->load->model('Cart_model');
	}
	
	public function index()
	{
		echo "<pre>";
		$contents = $this->cart->contents();
		print_r($contents);
		echo "</pre>";
		
		//$this->load->view('index');
	}
	
	public function add()
	{
		$id = $this->input->post('xtrl');
		$price = $this->input->post('price');
		$name = $this->input->post('ptitle');
		$qty = $this->input->post('qnty');
		$ship_type = $this->input->post('ship_type');
		$opt = $this->input->post('opt');
		if(count($opt) !== 0)
		{
			foreach($opt as $okey => $oval){
				$varietions = $this->input->post('varietion'.$oval);
				foreach($varietions as $key => $value)
				{
					$varient = $this->input->post('varient'.$oval);
					$option = $this->input->post('option'.$oval);
					$varietion[$value] = $option;
					$sv[$varient] = $option;
				}	
			}
			if(!in_array("", $varietion))
			{
				$data = array(
					'id' => $id,
					'qty' => $qty,
					'name' => $name,
					'price' => $price,
					'options' => array("ship_type" => $ship_type, "varietion" => $varietion, "sve" => $sv)
				);
				$this->cart->product_name_rules = '[:print:]';
				$this->cart->insert($data);
				echo $this->_ajax_crtresult();
				exit;
			}else
			{
				ksort($varietion);
				foreach($varietion as $key => $value) {
					$value = trim($value);
					if(empty($value)):
						$empty_varient =  $key;
					endif;
				}
				$has_optinos = true;
				echo $this->_ajax_crtresult($has_optinos, $empty_varient);
				exit;
			}
		}else
		{
			$data = array(
				'id' => $id,
				'qty' => $qty,
				'name' => $name,
				'price' => $price,
				'options' => array("ship_type" => $ship_type, "varietion" => '', "sve" => '')
			);
			$this->cart->product_name_rules = '[:print:]';
			$this->cart->insert($data);
			echo $this->_ajax_crtresult();
			exit;
		}
	}
	
	public function _ajax_crtresult($has_optinos=false, $empty_varient=null)
	{
		if($has_optinos !== true)
		{
			$total_items = $this->cart->total_items().' items';
			$success_content = '<div class="successMsg"><i class="fa fa-check"></i> &nbsp;&nbsp;Product Successfuly Added <span class="fa fa-times"></span></div>';
			$result = array("status" => "ok", "success_content" => $success_content, "total_items" => $total_items);
			return json_encode($result);
			exit;
		}else
		{
			$warning_content = '<div class="wanrningMsg"><i class="fa fa-warning"></i> &nbsp;&nbsp;Please select product '.$empty_varient.' <span class="fa fa-times"></span></div>';
			$result = array("status" => "warning", "warning_content" => $warning_content);
			return json_encode($result);
			exit;
		}
	}
	
	public function put_voucher()
	{
		$voucher_code = html_escape($this->input->post('vhoucher'));
		$check_available = $this->Cart_model->check_voucher($voucher_code);
		if($check_available == TRUE)
		{
			//do somthing
			$sess['voucher_discount'] = $check_available['voucher_discount_price'];
			$sess['voucher_code'] = $voucher_code;
			$this->session->set_userdata($sess);
			echo $this->_ajax_single_cart();
			exit;
			
		}else
		{
			// error somthing
			exit;
		}
	}
	
	public function update()
	{
		$rowid = $this->input->post('row');
		$qty = $this->input->post('qty');
		$data = array(
			'rowid' => $rowid,
			'qty' => $qty,
		);
		$this->cart->update($data);
		echo $this->_crt_content();
	}
	
	public function remove()
	{
		$rowid = $this->input->post('row');
		$data = array(
			'rowid' => $rowid,
			'qty' => 0,
		);
		$this->cart->update($data);
		echo $this->_crt_content();
	}
	
	public function _crt_content()
	{
		$content = '';
		if($this->cart->total_items() !== 0):
		$content .= '<h1>Shopping Cart &nbsp;&nbsp;-&nbsp;&nbsp;'.count($this->cart->contents()).' Items</h1>'; 
		$cart_items = $this->cart->contents();
		foreach($cart_items as $item):
		$rndval = rand(68729, 64739);
		$get_shipcost = $this->Cart_model->get_product_shipcost($item['id']);
		$crt_productinfo = $this->Cart_model->crt_single_product($item['id']);
		$get_photo = $this->Cart_model->get_default_photo($item['id']);
		$photo = $get_photo['image_url'];
		$img = explode('.', $photo);
		$shop_username = $this->Outer_model->shop_username($crt_productinfo['product_vendor_id']);
		$photo_dir = attachment_url().'vendors/'.$shop_username.'/products/'.$img[0].'_thumb.'.$img[1];
		$get_reviews = $this->Cart_model->get_product_reviews($item['id']);
		$max = 0;
		$n = count($get_reviews); // get the count of comments
		if($n !== 0){
			foreach ($get_reviews as $rate => $count) { // iterate through array
				$max = $max + intval($count['review_rating']);
			}
			$average = ceil($max / $n);
		}else{
			$average = 0;
		}
		$content .= '<div class="single-cart-item">';
			$content .= '<span class="glyphicon glyphicon-remove remove-item" onclick="removeItem('.$item['id'].$rndval.');"></span>';
			$content .= '<div class="cart-item-leftbox pull-left">';
				$content .= '<div class="cart-item-thumb"><img src="'.$photo_dir.'" alt="item" /></div>';
				$content .= '<p class="cart-item-rating">';
				$x = 1;
					for($x=1;$x<=$average;$x++) {
						$content .= '<span class="glyphicon glyphicon-star" aria-hidden="true"></span>';
					}
					while ($x<=5){
						$content .= '<span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>';
						$x++;
					}
					$content .= '<span class="total-reviewed">- ('.$n.')</span>';
				$content .= '</p>';
			$content .= '</div>';
			$content .= '<div class="cart-item-rightbox pull-left">';
				$content .= '<h2 class="cart-item-title"><a href="'.base_url('product/'.$crt_productinfo['product_slug']).'">'.$item['name'].'</a></h2>'; 
				$get_varients = $item['options']['sve'];
				if(!empty($get_varients) && count($get_varients) > 0):
				$content .= '<p class="cart-item-variations">'; 
				foreach($get_varients as $vkey => $vval):
				$get_titles = $this->Cart_model->get_verient_options_title($vkey, $vval, $item['id']);
				$content .= '<strong>'.$get_titles['varient_title'].'</strong>'.' : '.$get_titles['option_name'].' &nbsp;&nbsp;&nbsp;';
				endforeach;
				$content .= '</p>';
				endif;
				if($crt_productinfo['product_minorder_qty']):
				$content .= '<p class="cart-item-qty"><input type="text" name="qty" onchange="updateItem('.$item['id'].$rndval.', this.value)" data-bts-min="'.$crt_productinfo['product_minorder_qty'].'" class="qunty" value="'.$item['qty'].'" /> <strong class="itmsub">Sub Total : &nbsp;TK.'.$this->cart->format_number($item['subtotal']).'</strong></p>';
				else:
				$content .= '<p class="cart-item-qty"><input type="text" name="qty" onchange="updateItem('.$item['id'].$rndval.', this.value)" data-bts-min="1" class="qunty" value="'.$item['qty'].'" /> <strong class="itmsub">Sub Total : &nbsp;TK.'.$this->cart->format_number($item['subtotal']).'</strong></p>';
				endif;
				//Shipping cost count
				if($crt_productinfo['product_freeship_overqty'] && $crt_productinfo['product_freeship_overqty'] <= $item['qty']):
					if($item['options']['ship_type'] === '1'):
						$shipping_type = 'Local';
						$arrival_time = $crt_productinfo['product_local_shipping_time'];
						$shipping_cost = $this->cart->format_number(0);
					else:
						$shipping_type = 'International';
						$arrival_time = $crt_productinfo['product_global_shipping_time'];
						$shipping_cost = $this->cart->format_number(0);
					endif;
				else:
					if($item['options']['ship_type'] === '1'):
						$shipping_type = 'Local';
						$arrival_time = $crt_productinfo['product_local_shipping_time'];
						if($crt_productinfo['product_shipcost_overqty'] && $item['qty'] > 1):
						$ship_overqty = (intval($get_shipcost['product_local_shipping_cost']) / 100) * intval($crt_productinfo['product_shipcost_overqty']);
						$overship = $ship_overqty * intval($item['qty']);
						$shipping_cost = intval($get_shipcost['product_local_shipping_cost']) + $overship;
						else:
						$shipping_cost = intval($get_shipcost['product_local_shipping_cost']) * intval($item['qty']);
						endif;
					else:
						$shipping_type = 'International';
						$arrival_time = $crt_productinfo['product_global_shipping_time'];
						if($crt_productinfo['product_shipcost_overqty'] && $item['qty'] > 1):
						$ship_overqty = (intval($get_shipcost['product_global_shipping_cost']) / 100) * intval($crt_productinfo['product_shipcost_overqty']);
						$overship = $ship_overqty * intval($item['qty']);
						$shipping_cost = intval($get_shipcost['product_global_shipping_cost']) + $overship;
						else:
						$shipping_cost = intval($get_shipcost['product_global_shipping_cost']) * intval($item['qty']);
						endif;
					endif;
				endif;
				//End Shipping Cost Count
				$total_shipping[] = $shipping_cost;
				$content .= '<p class="cart-item-shipping"><strong style="color:#0a0">Seller : '.$crt_productinfo['shop_company_name'].'</strong></p>';
				$content .= '<p class="cart-item-shipping"><strong>Shipping</strong> : TK.'.$this->cart->format_number($shipping_cost).'</p>';
				$content .= '<p class="cart-item-arrival-time"><strong>Shipping Type</strong> : '.$shipping_type.'</p>';
				$content .= '<p class="cart-item-arrival-time"><strong>Estimated Arrival Time</strong> : '.$arrival_time.'</p>';
				if($crt_productinfo['product_discount_price']):
					$content .= '<p class="cart-item-price"><del>TK.'.$crt_productinfo['product_price'].''; 
					if($crt_productinfo['product_metering_type']):
					$content .= '&nbsp;/&nbsp;'.$crt_productinfo['product_metering_type'];
					else:
					$content .= null;
					endif;
					$content .= '</del> &nbsp;&nbsp;&nbsp; <strong>TK.'.$crt_productinfo['product_discount_price'].' ';
					
					if($crt_productinfo['product_metering_type']):
					$content .= '&nbsp;/&nbsp;'.$crt_productinfo['product_metering_type'];
					else:
					$content .= null;
					endif;
					$content .= '</strong></p>';
				else:
					$content .= '<p class="cart-item-price"><strong>TK.'.$crt_productinfo['product_price'].' ';
					if($crt_productinfo['product_metering_type']):
					$content .= '&nbsp;/&nbsp;'.$crt_productinfo['product_metering_type'];
					else:
					$content .= null;
					endif;
					$content .= '</strong></p>';
				endif;
			$content .= '</div>';
			$content .= '<span class="hiddnrow-'.$item['id'].$rndval.'" data-row="'.$item['rowid'].'"></span>';
		$content .= '</div>';
		$content .= '
								<script>
									$(".qunty").TouchSpin();
								</script>
							';
		endforeach;
			$shipping_cost_total = $this->cart->format_number(array_sum($total_shipping));
			$total_items = $this->cart->total_items();
			$items_amount = $this->cart->format_number($this->cart->total());
			$total_amount = $this->cart->format_number(intval($this->cart->total()) + intval($shipping_cost_total));
			$result = array("status" => "ok", "content" => $content, 'items_amount' => $items_amount, 'total_amount' => $total_amount, 'ship_amount' => $shipping_cost_total, "total_items" => $total_items);
			return json_encode($result);
			exit;
		else:
			$total_items = $this->cart->total_items();
			$noitems = '
						<div class="row">
							<div class="col-lg-12">
								<div class="nocrt-items">
									<h2>Cart Is Empty</h2>
									<a href="'.site_url().'">Continue Shopping</a>
								</div>
							</div>
						</div>
					';
			$result = array("status" => "nok", "noitems" => $noitems, "total_items" => $total_items);
			return json_encode($result);
			exit;
		endif;
	}
}
