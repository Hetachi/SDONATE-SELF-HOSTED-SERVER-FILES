<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{

    if(isset($_POST['addsale'])){

		$sql = $dbcon->prepare("INSERT INTO sales (name, packages, discounttype, discount, starts, ends) VALUES(:name, :packages, :discounttype, :discount, :starts, :ends)");

        if(strlen($_POST['name']) < 255 && strlen($_POST['name']) > 0){
			if($_POST['discounttype'] != 'Percent Off' || $_POST['discounttype'] != 'Money Off' || $_POST['discounttype'] != 'Set Price'){
				if (is_numeric($_POST['discount'])){
					if(!empty($_POST['starts'])){
						if(!empty($_POST['expires'])){
							if ($_POST['expires'] > $_POST['starts']){

								$salePackages = [];
								$sql1 = $dbcon->prepare("SELECT id FROM packages");
							    $sql1->execute();
							    $packages = $sql1->fetchAll(PDO::FETCH_ASSOC);

								foreach ($packages as $key => $value) {
									if (isset($_POST['salepackage-' . $value['id']])){
										array_push($salePackages, $value['id']);
									}
								}

								$values = array(':name' => $_POST['name'], ':packages' => json_encode($salePackages), ':discounttype' => $_POST['discounttype'], ':discount' => $_POST['discount'], ':starts' => $_POST['starts'], ':ends' => $_POST['expires']);
								$sql->execute($values);

							} else {
								array_push($pageError, "End time must be after start time.");
							}
						} else {
							array_push($pageError, "You must choose an end time.");
						}
					} else {
							array_push($pageError, "You must choose a start time.");
					}
				} else {
					array_push($pageError, "Discount must be a valid number.");
				}
			} else {
				array_push($pageError, "Invalid discount type.");
			}
		} else {
			array_push($pageError, "Name must be between 1 and 255 characters long.");
		}

    }

	if(isset($_POST['editsale'])){

		$sql = $dbcon->prepare("UPDATE sales SET name=:name, packages=:packages, discounttype=:discounttype, discount=:discount, starts=:starts, ends=:ends WHERE id=:id");

        if(strlen($_POST['name']) < 255 && strlen($_POST['name']) > 0){
			if($_POST['discounttype'] != 'Percent Off' || $_POST['discounttype'] != 'Money Off' || $_POST['discounttype'] != 'Set Price'){
				if (is_numeric($_POST['discount'])){
					if(!empty($_POST['starts'])){
						if(!empty($_POST['expires'])){
							if ($_POST['expires'] > $_POST['starts']){

								$salePackages = [];
								$sql1 = $dbcon->prepare("SELECT id FROM packages");
							    $sql1->execute();
							    $packages = $sql1->fetchAll(PDO::FETCH_ASSOC);

								foreach ($packages as $key => $value) {
									if (isset($_POST['salepackage-' . $value['id']])){
										array_push($salePackages, $value['id']);
									}
								}

								$values = array(':name' => $_POST['name'], ':packages' => json_encode($salePackages), ':discounttype' => $_POST['discounttype'], ':discount' => $_POST['discount'], ':starts' => $_POST['starts'], ':ends' => $_POST['expires'], ':id' => $_POST['editsale']);
								$sql->execute($values);

							} else {
								array_push($pageError, "End time must be after start time.");
							}
						} else {
							array_push($pageError, "You must choose an end time.");
						}
					} else {
						array_push($pageError, "You must choose a start time.");
					}
				} else {
					array_push($pageError, "Discount must be a valid number.");
				}
			} else {
				array_push($pageError, "Invalid discount type.");
			}
		} else {
			array_push($pageError, "Name must be between 1 and 255 characters long.");
		}

    }

    if(isset($_POST['deletesale'])){
        $sql = $dbcon->prepare("DELETE FROM sales WHERE id=:id");
        $value = array(':id' => $_POST['deletesale']);
        $sql->execute($value);

    }

	if(isset($_POST['addcoupon'])){

		$sql = $dbcon->prepare("INSERT INTO coupons (code, packages, discounttype, discount, ends, maxuses, maxusesperperson) VALUES(:code, :packages, :discounttype, :discount, :ends, :maxuses, :maxusesperperson)");

        if(strlen($_POST['code']) < 255 && strlen($_POST['code']) > 0){
			if($_POST['discounttype'] != 'Percent Off' || $_POST['discounttype'] != 'Money Off' || $_POST['discounttype'] != 'Set Price'){
				if (is_numeric($_POST['discount'])){
					if(!empty($_POST['expires'])){
						if (ctype_digit($_POST['maxuses'])) {
							if (ctype_digit($_POST['maxusesperperson'])) {

								$salePackages = [];
								$sql1 = $dbcon->prepare("SELECT id FROM packages");
							    $sql1->execute();
							    $packages = $sql1->fetchAll(PDO::FETCH_ASSOC);

								foreach ($packages as $key => $value) {
									if (isset($_POST['couponpackage-' . $value['id']])){
										array_push($salePackages, $value['id']);
									}
								}

								$values = array(':code' => $_POST['code'], ':packages' => json_encode($salePackages), ':discounttype' => $_POST['discounttype'], ':discount' => $_POST['discount'], ':ends' => $_POST['expires'], ':maxuses' => $_POST['maxuses'], ':maxusesperperson' => $_POST['maxusesperperson']);
								$sql->execute($values);

							} else {
								array_push($pageError, "Max Uses Per Person must be a valid integer.");
							}
						} else {
							array_push($pageError, "Max Uses must be a valid integer.");
						}
					} else {
						array_push($pageError, "You must choose an expiration date.");
					}
				} else {
					array_push($pageError, "Discount must be a valid number.");
				}
			} else {
				array_push($pageError, "Invalid discount type.");
			}
		} else {
			array_push($pageError, "Code must be between 1 and 255 characters long.");
		}

    }

	if(isset($_POST['editcoupon'])){

		$sql = $dbcon->prepare("UPDATE coupons SET code=:code, packages=:packages, discounttype=:discounttype, discount=:discount, ends=:ends, maxuses=:maxuses, maxusesperperson=:maxusesperperson WHERE id=:id");

		if(strlen($_POST['code']) < 255 && strlen($_POST['code']) > 0){
			if($_POST['discounttype'] != 'Percent Off' || $_POST['discounttype'] != 'Money Off' || $_POST['discounttype'] != 'Set Price'){
				if (is_numeric($_POST['discount'])){
					if(!empty($_POST['expires'])){
						if (ctype_digit($_POST['maxuses'])) {
							if (ctype_digit($_POST['maxusesperperson'])) {

								$salePackages = [];
								$sql1 = $dbcon->prepare("SELECT id FROM packages");
								$sql1->execute();
								$packages = $sql1->fetchAll(PDO::FETCH_ASSOC);

								foreach ($packages as $key => $value) {
									if (isset($_POST['couponpackage-' . $value['id']])){
										array_push($salePackages, $value['id']);
									}
								}

								$values = array(':code' => $_POST['code'], ':packages' => json_encode($salePackages), ':discounttype' => $_POST['discounttype'], ':discount' => $_POST['discount'], ':ends' => $_POST['expires'], ':maxuses' => $_POST['maxuses'], ':maxusesperperson' => $_POST['maxusesperperson'], ':id' => $_POST['editcoupon']);
								$sql->execute($values);

							} else {
								array_push($pageError, "Max Uses Per Person must be a valid integer.");
							}
						} else {
							array_push($pageError, "Max Uses must be a valid integer.");
						}
					} else {
						array_push($pageError, "You must choose an expiration date.");
					}
				} else {
					array_push($pageError, "Discount must be a valid number.");
				}
			} else {
				array_push($pageError, "Invalid discount type.");
			}
		} else {
			array_push($pageError, "Code must be between 1 and 255 characters long.");
		}

	}

	if(isset($_POST['deletecoupon'])){
        $sql = $dbcon->prepare("DELETE FROM coupons WHERE id=:id");
        $value = array(':id' => $_POST['deletecoupon']);
        $sql->execute($value);

    }

}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
