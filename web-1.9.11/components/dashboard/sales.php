<?php

    require(dirname(__FILE__) . '/../../require/classes.php');
    $user = new User();
    if (!$user->IsAdmin())
    {
        die("You must be an admin to see this page.");
    }

    $sql = $dbcon->prepare("SELECT * FROM sales ORDER BY ends");
    $sql->execute();
    $saleCount = $sql->rowCount();
    $sales = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($sales, "escapeHTML");

    $salesJS = json_encode($sales);

	$salesDiscount = array();
    foreach($sales as $key => $value){
        if($sales[$key]['discounttype'] == 'Percent Off'){
            $salesDiscount[$sales[$key]['id']] = $sales[$key]['discount'] . '% off';
        } elseif($sales[$key]['discounttype'] == 'Set Price') {
            $salesDiscount[$sales[$key]['id']] = $currencysymbol . $sales[$key]['discount'] . ' (Set Price)';
        } elseif($sales[$key]['discounttype'] == 'Money Off') {
            $salesDiscount[$sales[$key]['id']] = $currencysymbol . $sales[$key]['discount'] . ' off';
        }
    }

	$sql = $dbcon->prepare("SELECT * FROM coupons ORDER BY ends");
    $sql->execute();
    $couponCount = $sql->rowCount();
    $coupons = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($coupons, "escapeHTML");

    $couponsJS = json_encode($coupons);

	$couponsDiscount = array();
    foreach($coupons as $key => $value){
        if($coupons[$key]['discounttype'] == 'Percent Off'){
            $couponsDiscount[$coupons[$key]['id']] = $coupons[$key]['discount'] . '% off';
        } elseif($coupons[$key]['discounttype'] == 'Set Price') {
            $couponsDiscount[$coupons[$key]['id']] = $currencysymbol . $coupons[$key]['discount'] . ' (Set Price)';
        } elseif($coupons[$key]['discounttype'] == 'Money Off') {
            $couponsDiscount[$coupons[$key]['id']] = $currencysymbol . $coupons[$key]['discount'] . ' off';
        }
    }

	$sql = $dbcon->prepare("SELECT coupon FROM transactions");
    $sql->execute();
    $transactions = $sql->fetchAll(PDO::FETCH_ASSOC);

	$couponsTransactions = array();
    foreach($coupons as $key => $value){
		$transactionsWithCoupon = 0;
		foreach ($transactions as $key1 => $value1) {
			if($value1['coupon'] === $value['code']) {
				$transactionsWithCoupon++;
			}
		}
        $couponsTransactions[$coupons[$key]['id']] = $transactionsWithCoupon;
    }

	$sql = $dbcon->prepare("SELECT * FROM packages ORDER BY game, title");
    $sql->execute();
    $packages = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($packages, "escapeHTML");
	$packagesJS = json_encode($packages);

?>
<link rel="stylesheet" href="https://unpkg.com/flatpickr/dist/flatpickr.min.css">
<script src="https://unpkg.com/flatpickr"></script>
<div id="dashboard-content-container">
    <p id="dashboard-page-title">Sales and Coupons</p>
    <div class="row">
        <div class="col-md-12">
			<p class="setting-title">Sales</p>
            <div class="dashboard-stat-large">
                <div class="statistics-title">&nbsp;</div>
                <div class="statistics-content table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The title of the sale.">?</button></th>
								<th>Discount<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Discount applied to the packages on sale.">?</button></th>
								<th>Starts<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Date and time the sale starts.">?</button></th>
                                <th>Ends<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Date and time the sale ends.">?</button></th>
                                <th style="text-align: center;"><?= getLangString("edit") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if($saleCount === 0){
                				print('
                					<tr>
                						<td>There are no sales.</td>
                					</tr>
                					');
                			} else {

                				foreach($sales as $key => $value){
                					print('
                						<tr>
                							<td>' . $sales[$key]['name'] . '</td>
                							<td>' . $salesDiscount[$value['id']] . '</td>
											<td>' . $sales[$key]['starts'] . '</td>
											<td>' . $sales[$key]['ends'] . '</td>
                							<td style="text-align: center;"><a href="#" onclick="editSale(' . $key . ');"><span class="glyphicon glyphicon-pencil"></span></a></td>
                						</tr>
                						');
                				}

                			}
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
			<button class="submit-button" onclick="addSale();" style="margin-left: 0px; margin-bottom: 60px;">Add Sale</button>
        </div>
		<div class="col-md-12">
			<p class="setting-title">Coupons</p>
            <div class="dashboard-stat-large">
                <div class="statistics-title">&nbsp;</div>
                <div class="statistics-content table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The coupon code.">?</button></th>
								<th>Discount<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Discount applied to the packages using the coupon.">?</button></th>
                                <th>Uses Left<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Amount of uses the coupon has left.">?</button></th>
								<th>Expires<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Discount applied to the packages using the coupon.">?</button></th>
                                <th style="text-align: center;"><?= getLangString("edit") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if($couponCount === 0){
                				print('
                					<tr>
                						<td>There are no coupons.</td>
                					</tr>
                					');
                			} else {

                				foreach($coupons as $key => $value){
                					print('
                						<tr>
                							<td>' . $coupons[$key]['code'] . '</td>
                							<td>' . $couponsDiscount[$value['id']] . '</td>
											<td>' . ($coupons[$key]['maxuses'] - $couponsTransactions[$value['id']]) . '</td>
											<td>' . $coupons[$key]['ends'] . '</td>
                							<td style="text-align: center;"><a href="#" onclick="editCoupon(' . $key . ');"><span class="glyphicon glyphicon-pencil"></span></a></td>
                						</tr>
                						');
                				}

                			}
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
			<button class="submit-button" onclick="addCoupon();" style="margin-left: 0px; margin-bottom: 60px;">Add Coupon</button>
        </div>
    </div>
</div>
<script>

	var packages = <?= $packagesJS ?>;
	var sales = <?= $salesJS ?>;
	var coupons = <?= $couponsJS ?>;

	function addSale(){
		var html = '' +
			'<form action="ajax/dashboard/sales.php" method="post" enctype="multipart/form-data">\n' +
				'<input type="hidden" name="addsale">\n' +
				'<p id="errorbox-title">Add Sale</p>\n' +
				'<p class="setting-title">Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the name of the sale (This is not diplayed and is only used for organisation)">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="name" class="settings-text-input">\n' +
				'<p class="setting-title"><?= getLangString("packages") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the packages this sale applies to.">?</button></p>\n';

		$.each(packages, function(key, value){
			html += '<input style="margin-bottom: 20px;" type="checkbox" name="salepackage-' + value.id + '" value="salepackage-' + value.id + '"><label for="' + value.id + '">' + value.title + '</label><br>\n';
		});

		html += '' +
				'<p class="setting-title">Discount Type<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the type of discount.">?</button></p>\n' +
				'<select class="dropdown" style="margin-bottom: 20px;" name="discounttype">\n' +
					'<option value="Percent Off">Percent Off</option>\n' +
					'<option value="Money Off">Money Off</option>\n' +
					'<option value="Set Price">Set Price</option>\n' +
				'</select>\n' +
				'<p class="setting-title">Discount<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the discount applied by the sale.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="discount" class="settings-text-input">\n' +
				'<p class="setting-title">Start Time<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select when the sale starts.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="starts" id="starts" class="settings-text-input">\n' +
				'<p class="setting-title">End Time<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select when the sale ends.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="expires" id="expires" class="settings-text-input">\n' +
				'<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
			'</form>';
		showError(html);
		enableToolTips();
		flatpickr("#starts", { enableTime: true, minDate: "today" });
		flatpickr("#expires", { enableTime: true, minDate: "today" });
		listenForSubmit();
	}

	function editSale(key){
		var html = '' +
			'<form action="ajax/dashboard/sales.php" method="post" enctype="multipart/form-data">\n' +
				'<input type="hidden" name="editsale" value="' + sales[key]["id"] + '">\n' +
				'<p id="errorbox-title">Edit Sale</p>\n' +
				'<p class="setting-title">Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the name of the sale (This is not diplayed and is only used for organisation)">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="name" class="settings-text-input" value="' + sales[key]["name"].replace('"', '\"') + '">\n' +
				'<p class="setting-title"><?= getLangString("packages") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the packages this sale applies to.">?</button></p>\n';

		$.each(packages, function(key1, value1){
			var packageChecked = "";
			for (i = 0; i < sales[key].packages.length; i++){
				if (value1.id == sales[key].packages[i]){
					packageChecked = "checked"
				}
			}
			html += '<input style="margin-bottom: 20px;" type="checkbox" name="salepackage-' + value1.id + '" value="salepackage-' + value1.id + '" ' + packageChecked + '><label for="' + value1.id + '">' + value1.title + '</label><br>\n';
		});

		html += '' +
				'<p class="setting-title">Discount Type<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the type of discount.">?</button></p>\n' +
				'<select class="dropdown" style="margin-bottom: 20px;" name="discounttype" id="discounttype">\n' +
					'<option value="Percent Off">Percent Off</option>\n' +
					'<option value="Money Off">Money Off</option>\n' +
					'<option value="Set Price">Set Price</option>\n' +
				'</select>\n' +
				'<p class="setting-title">Discount<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the discount applied by the sale.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="discount" class="settings-text-input" value="' + sales[key]["discount"] + '">\n' +
				'<p class="setting-title">Start Time<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select when the sale starts.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="starts" id="starts" class="settings-text-input" value="' + sales[key]["starts"] + '">\n' +
				'<p class="setting-title">End Time<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select when the sale ends.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="expires" id="expires" class="settings-text-input" value="' + sales[key]["ends"] + '">\n' +
				'<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
				'<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deleteSale(' + key + ');"><?= getLangString("delete") ?></button>\n' +
			'</form>';
		showError(html);
		enableToolTips();
		$("#discounttype").val(sales[key]["discounttype"]);
		flatpickr("#starts", { enableTime: true, minDate: "today" });
		flatpickr("#expires", { enableTime: true, minDate: "today" });
		listenForSubmit();
	}

	function deleteSale(key){
        var html = '' +
            '<form action="ajax/dashboard/sales.php" method="post">\n' +
                '<p style="text-align: center;">Do you really want to delete the sale ' + sales[key]["name"] + '?</p>\n' +
                '<input type="hidden" value="' + sales[key]["id"] + '" name="deletesale">\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("delete") ?>" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;">\n' +
            '</form>';
        showError1(html);
        listenForSubmit();
    }

	function addCoupon(){
		var html = '' +
			'<form action="ajax/dashboard/sales.php" method="post" enctype="multipart/form-data">\n' +
				'<input type="hidden" name="addcoupon">\n' +
				'<p id="errorbox-title">Add Coupon</p>\n' +
				'<p class="setting-title">Coupon Code<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the code the user needs to enter to get the discount at checkout.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="code" class="settings-text-input">\n' +
				'<p class="setting-title"><?= getLangString("packages") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the packages this coupon can be used on.">?</button></p>\n';

		$.each(packages, function(key, value){
			html += '<input style="margin-bottom: 20px;" type="checkbox" name="couponpackage-' + value.id + '" value="couponpackage-' + value.id + '"><label for="' + value.id + '">' + value.title + '</label><br>\n';
		});

		html += '' +
				'<p class="setting-title">Discount Type<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the type of discount.">?</button></p>\n' +
				'<select class="dropdown" style="margin-bottom: 20px;" name="discounttype">\n' +
					'<option value="Percent Off">Percent Off</option>\n' +
					'<option value="Money Off">Money Off</option>\n' +
					'<option value="Set Price">Set Price</option>\n' +
				'</select>\n' +
				'<p class="setting-title">Discount<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the discount applied by the coupon.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="discount" class="settings-text-input">\n' +
				'<p class="setting-title">Maximum Uses<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the amount of times this coupon can be used overall.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="maxuses" class="settings-text-input">\n' +
				'<p class="setting-title">Maximum Uses Per Person<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the amount of times this coupon can be used by a single user.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="maxusesperperson" class="settings-text-input">\n' +
				'<p class="setting-title">Expires<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select when the coupon expires.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="expires" id="expires" class="settings-text-input">\n' +
				'<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
			'</form>';
		showError(html);
		enableToolTips();
		flatpickr("#expires", { enableTime: true, minDate: "today" });
		listenForSubmit();
	}

	function editCoupon(key){
		var html = '' +
			'<form action="ajax/dashboard/sales.php" method="post" enctype="multipart/form-data">\n' +
				'<input type="hidden" name="editcoupon" value="' + coupons[key]["id"] + '">\n' +
				'<p id="errorbox-title">Edit Coupon</p>\n' +
				'<p class="setting-title">Coupon Code<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the code the user needs to enter to get the discount at checkout.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="code" class="settings-text-input" value="' + coupons[key]["code"].replace('"', '\"') + '">\n' +
				'<p class="setting-title"><?= getLangString("packages") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the packages this coupon can be used on.">?</button></p>\n';

		$.each(packages, function(key1, value1){
			var packageChecked = "";
			for (i = 0; i < coupons[key].packages.length; i++){
				if (value1.id == coupons[key].packages[i]){
					packageChecked = "checked"
				}
			}
			html += '<input style="margin-bottom: 20px;" type="checkbox" name="couponpackage-' + value1.id + '" value="couponpackage-' + value1.id + '" ' + packageChecked + '><label for="' + value1.id + '">' + value1.title + '</label><br>\n';
		});

		html += '' +
				'<p class="setting-title">Discount Type<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the type of discount.">?</button></p>\n' +
				'<select class="dropdown" style="margin-bottom: 20px;" name="discounttype" id="discounttype">\n' +
					'<option value="Percent Off">Percent Off</option>\n' +
					'<option value="Money Off">Money Off</option>\n' +
					'<option value="Set Price">Set Price</option>\n' +
				'</select>\n' +
				'<p class="setting-title">Discount<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the discount applied by the coupon.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="discount" class="settings-text-input" value="' + coupons[key]["discount"] + '">\n' +
				'<p class="setting-title">Maximum Uses<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the amount of times this coupon can be used overall.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="maxuses" class="settings-text-input" value="' + coupons[key]["maxuses"] + '">\n' +
				'<p class="setting-title">Maximum Uses Per Person<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the amount of times this coupon can be used by a single user.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="maxusesperperson" class="settings-text-input" value="' + coupons[key]["maxusesperperson"] + '">\n' +
				'<p class="setting-title">Expires<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select when the coupon expires.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="expires" id="expires" class="settings-text-input" value="' + coupons[key]["ends"] + '">\n' +
				'<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
				'<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deleteCoupon(' + key + ');"><?= getLangString("delete") ?></button>\n' +
			'</form>';
		showError(html);
		enableToolTips();
		$("#discounttype").val(coupons[key]["discounttype"]);
		flatpickr("#expires", { enableTime: true, minDate: "today" });
		listenForSubmit();
	}

	function deleteCoupon(key){
        var html = '' +
            '<form action="ajax/dashboard/sales.php" method="post">\n' +
                '<p style="text-align: center;">Do you really want to delete the coupon ' + coupons[key]["code"] + '?</p>\n' +
                '<input type="hidden" value="' + coupons[key]["id"] + '" name="deletecoupon">\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("delete") ?>" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;">\n' +
            '</form>';
        showError1(html);
        listenForSubmit();
    }

	function submissionSuccess(){
        location.reload(true);
    }

</script>
