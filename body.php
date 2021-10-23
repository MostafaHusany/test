<?php
/**
 * PDF invoice template body.
 *
 * This template can be overridden by copying it to youruploadsfolder/woocommerce-pdf-invoices/templates/invoice/simple/yourtemplatename/body.php.
 *
 * HOWEVER, on occasion WooCommerce PDF Invoices will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author  Bas Elbers
 * @package WooCommerce_PDF_Invoices/Templates
 * @version 0.0.1
 */
global $woocommerce, $post;

$templater                  = WPI()->templater();
$invoice                    = $templater->invoice;
$order                      = $invoice->order;
$line_items                 = $order->get_items( 'line_item' );
$formatted_shipping_address = $order->get_formatted_shipping_address();
$formatted_billing_address  = $order->get_formatted_billing_address();
$columns                    = $invoice->get_columns();
$color                      = $templater->get_option( 'bewpi_color_theme' );
$terms                      = $templater->get_option( 'bewpi_terms' );
?>

<div class="title">
	<div>
		<h2><?php echo esc_html( WPI()->get_option( 'template', 'title' ) ); ?></h2>
	</div>
	<div class="watermark">
		<?php
		if ( WPI()->get_option( 'template', 'show_payment_status' ) && $order->is_paid() ) {
			printf( '<h2 class="green">%s</h2>', esc_html__( 'Paid', 'woocommerce-pdf-invoices' ) );
		}

		do_action( 'wpi_watermark_end', $order, $invoice );
		?>
	</div>
</div>
<table cellpadding="0" cellspacing="0">
	<tr class="information">
		<td width="50%">
			<?php
			/**
			 * Invoice object.
			 *
			 * @var BEWPI_Invoice $invoice .
			 */
			
			foreach ( $invoice->get_invoice_info() as $info_id => $info ) {
				if ( empty( $info['value'] ) ) {
					continue;
				}

				printf( '<span class="%1$s">%2$s %3$s</span>', esc_attr( $info_id ), esc_html( $info['title'] ), esc_html( $info['value'] ) );
				echo '<br>';
			}
			?>
		</td>

		<td>
			<?php
			printf( '<strong>%s</strong><br />', esc_html__( 'Bill to:', 'woocommerce-pdf-invoices' ) );
			echo $formatted_billing_address;

			do_action( 'wpi_after_formatted_billing_address', $invoice );
			?>
		</td>

		<td>
			<?php
			if ( WPI()->get_option( 'template', 'show_ship_to' ) && ! WPI()->has_only_virtual_products( $order ) && ! empty( $formatted_shipping_address ) ) {
				printf( '<strong>%s</strong><br />', esc_html__( 'Ship to:', 'woocommerce-pdf-invoices' ) );
				echo $formatted_shipping_address;

				do_action( 'wpi_after_formatted_shipping_address', $invoice );
			}
			?>
		</td>
	</tr>
	<tr class="custom-information">
		<td colspan="3">
			<?php echo apply_filters( 'wpi_custom_information', '', $invoice ); ?>
		</td>
	</tr>
</table>

<table cellpadding="0" cellspacing="0">
	<thead>
	<tr class="heading" bgcolor="<?php echo esc_attr( $color ); ?>;">
		<?php
		foreach ( $columns as $key => $data ) {
			$templater->display_header_recursive( $key, $data );
		}
		?>
	</tr>
	</thead>
	<tbody>
	<?php
	if($order->get_payment_method() == "tabby_installments"){
		$order_payment = $order->get_payment_method();
		$insurance = 0;
		$order_package = 0;

		if( $order_payment== "tabby_installments"){
			foreach($order->get_items('fee') as $item_id => $item_fee ) {
				$fee_name = $item_fee->get_name();
				$fee_total = round($item_fee->get_total(), 2);
				if($fee_name == "اضافة تأمين على الشحنة (MT-INSUR-SMSA)"){
					$insurance =  $fee_total;
				}

				if($fee_name == "اضف باقة PCD SHIELD (MT-PC-SHIELD)"){
					$order_package = $fee_total;
				}

			}
			
	}

		$order_totals = 0;
		$all_fee = 0;
		$item_subtotals = 0;

		$currency = $order->get_currency();
		if($currency == "SAR"){
			$currency = "ر.س ";
		}
		$order_data = $order->get_data();
		$order_shipping = $order_data['shipping_total'];
		$order_subtotal = $order->get_subtotal();
		$resoum = ( $order->get_subtotal() + $order_shipping ) * 0.065;
		$order_subtotal_af = $order->get_subtotal() + $resoum;
		
		foreach ($order->get_items() as $item_id => $item ) {
			//Get the product ID
			$product_id = $item->get_product_id();

			//Get the variation ID
			$variation_id = $item->get_variation_id();
		
			//Get the WC_Product object
			$product = $item->get_product();
			$product_price = $product->get_price();
			if( $order_payment== "tabby_installments"){
				$product_id = $item->get_product_id();

				//Get the variation ID
				$variation_id = $item->get_variation_id();
				$product = $item->get_product();
				$product_price = $product->get_price();
				// The quantity
				$quantity = $item->get_quantity();
						
				// The product name
				$product_name = $item->get_name(); // … OR: $product->get_name();
									
				//Get the product SKU (using WC_Product method)
				$sku = $product->get_sku();

				$single_item_price = ($item->get_subtotal() / $quantity);

				$item_subtotals = $order->get_subtotal();

				$single_item_bf = ($single_item_price / $item_subtotals);

				$single_item_fee = $single_item_bf * $resoum;

				$single_item_af = $single_item_price + $single_item_fee;
								
				$product_price = ($single_item_price + $single_item_fee) * $quantity;

				$product_price = number_format((float)$product_price, 2, '.', '');

				$product_price = wc_price( $product_price, array( 'currency' => $this->order->get_currency() ) );

				$single_item_price += $single_item_fee;

				$single_item_price = wc_price( $single_item_price, array( 'currency' => $this->order->get_currency() ) );

				
			}else{
				$active_price   = wc_price( round($product->get_price()), array( 'currency' => $this->order->get_currency() ) ); 
			}

			echo '<tr class="item">';
			echo '<td>'.$product_name.'</td>';
			echo '<td>'.$single_item_price.'</td>';
			echo '<td>'.$quantity.'</td>';
			echo '<td>'.$product_price.'</td>';
			echo '</tr>';
		}
		$all_fee = ($order_subtotal_af + $order_shipping + $insurance) * 0.15;
	}else{
		foreach ( $invoice->get_columns_data() as $index => $row ) {
			echo '<tr class="item">';
			
			
			// Display row data.
			foreach ( $row as $column_key => $data ) {
				$templater->display_data_recursive( $column_key, $data );
			}

			echo '</tr>';
		}
	}
	?>

	<tr class="spacer">
		<td></td>
	</tr>

	</tbody>
</table>
<?php

	if($order->get_payment_method() == "tabby_installments"){
		// Iterating through order fee items ONLY
		$order_subtotal2 = $order->get_subtotal() + $resoum;
		?>
		<table cellpadding="0" cellspacing="0">
			<tbody>

				<tr class="total">
					<td width="50%">
						
					</td>

					<td width="25%" align="left" class="border">
						<strong><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></strong>
					</td>

					<td width="25%" align="right" class="border">
						<?php echo wc_price( $order_subtotal2, array( 'currency' => $this->order->get_currency() ) ); ?>
					</td>
				</tr>

				<tr class="total">
					<td width="50%">
						
					</td>

					<td width="25%" align="left" class="border">
						<strong><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></strong>
					</td>

					<td width="25%" align="right" class="border">
						<?php echo wc_price( $order_data['shipping_total'], array( 'currency' => $this->order->get_currency() ) ); ?>
					</td>
				</tr>

				<?php 
					if($insurance > 0){
				?>

				<tr class="total">
					<td width="50%">
						
					</td>

					<td width="25%" align="left" class="border">
						<strong><?php esc_html_e( 'تأمين', 'woocommerce' ); ?></strong>
					</td>

					<td width="25%" align="right" class="border">
						<?php
							echo wc_price( $insurance, array( 'currency' => $this->order->get_currency() ) ); 
						?>
					</td>
				</tr>

				<?php } ?>

				<?php 
					if($order_package > 0){
				?>

				<tr class="total">
					<td width="50%">
						
					</td>

					<td width="25%" align="left" class="border">
						<strong><?php esc_html_e( 'باقة PCD SHIELD (MT-PC-SHIELD):	', 'woocommerce' ); ?></strong>
					</td>

					<td width="25%" align="right" class="border">
						<?php
							echo wc_price( $order_package, array( 'currency' => $this->order->get_currency() ) ); 
						?>
					</td>
				</tr>

				<?php } ?>

				<tr class="total">
					<td width="50%">
						
					</td>

					<td width="25%" align="left" class="border">
						<strong><?php esc_html_e( 'ضريبة القيمة المضافة 15%', 'woocommerce' ); ?></strong>
					</td>

					<td width="25%" align="right" class="border">
						<?php echo wc_price( $all_fee, array( 'currency' => $this->order->get_currency() ) ); ?>
					</td>
				</tr> 

				<tr class="total">
					<td width="50%">
						
					</td>

					<td width="25%" align="left" class="border last">
						<strong><?php esc_html_e( 'Total', 'woocommerce' ); ?></strong>
					</td>

					<td width="25%" align="right" class="border last">
						<?php
						$order_ftotal = $order->get_total();
						$order_ftotal = number_format((float)$order_ftotal, 2, '.', '');
						echo wc_price( $order_ftotal, array( 'currency' => $this->order->get_currency() ) ); ?>
					</td>
				</tr> 


			</tbody>
		</table>
		<?php
		
	}else{
?>
	<table cellpadding="0" cellspacing="0">
		<tbody>

		<?php
		$i      = 1;
		$length = count( $invoice->get_order_item_totals() );
		foreach ( $invoice->get_order_item_totals() as $key => $total ) {
			$class = str_replace( '_', '-', $key );
			?>

			<tr class="total">
				<td width="50%">
					<?php do_action( 'wpi_order_item_totals_left', $key, $invoice ); ?>
				</td>

				<td width="25%" align="left" class="border <?php echo $i === $length ? 'last' : ''; ?> <?php echo esc_attr( $class ); ?>">
					<?php echo $total['label']; ?>
				</td>

				<td width="25%" align="right" class="border <?php echo $i === $length ? 'last' : ''; ?> <?php echo esc_attr( $class ); ?>">
					<?php echo str_replace( '&nbsp;', '', $total['value'] ); ?>
				</td>
			</tr>

			<?php
			$i ++;
		}
		?>
		</tbody>
	</table>
<?php } ?>

<table class="notes" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
			// Customer notes.
			if ( WPI()->get_option( 'template', 'show_customer_notes' ) ) {
				// Note added by customer.
				$customer_note = BEWPI_WC_Order_Compatibility::get_customer_note( $order );
				if ( $customer_note ) {
					printf( '<strong>' . __( 'Note from customer: %s', 'woocommerce-pdf-invoices' ) . '</strong><br>', nl2br( $customer_note ) );
				}

				// Notes added by administrator on 'Edit Order' page.
				foreach ( $order->get_customer_order_notes() as $custom_order_note ) {
					printf( '<strong>' . __( 'Note to customer: %s', 'woocommerce-pdf-invoices' ) . '</strong><br>', nl2br( $custom_order_note->comment_content ) );
				}
			}
			?>
		</td>
	</tr>

	<tr>
		<td>
			<?php
			// Zero Rated VAT message.
			if ( 'true' === WPI()->get_meta( $order, '_vat_number_is_valid' ) && count( $order->get_tax_totals() ) === 0 ) {
				echo esc_html__( 'Zero rated for VAT as customer has supplied EU VAT number', 'woocommerce-pdf-invoices' ) . '<br>';
			}
			?>
		</td>
	</tr>
</table>

<?php if ( $terms ) { ?>
	<!-- Using div to position absolute the block. -->
	<div class="terms">
		<table>
			<tr>
				<td style="border: 1px solid #000;">
					<?php echo nl2br( $terms ); ?>
				</td>
			</tr>
		</table>
	</div>
<?php } ?>
