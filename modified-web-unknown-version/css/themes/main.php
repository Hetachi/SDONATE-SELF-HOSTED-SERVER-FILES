<?php

print('

* {
	margin: 0;
	padding: 0;
	font-family: \'' . $mainfont . '\', sans-serif;
	font-weight: 300;
	color: inherit;
}

body {
	color: ' . $mainfontcolor . ';
}

a {
	text-decoration: none;
	color: inherit;
}

a:hover {
	text-decoration: none;
	cursor: pointer;
	color: inherit;
}

a:visited {
	text-decoration: none;
	color: inherit;
}

a:active {
	text-decoration: none;
	color: inherit;
}

ul {
	margin-bottom: 0;
}

th {
	white-space: nowrap;
}

td {
	white-space: nowrap;
	font-size: 18px;
}

label {
	font-weight: inherit;
}

.underlined-link {
	text-decoration: underline;
}

#table-container {
	position: absolute;
	display: none;
	height: 100%;
	width: 100%;
	z-index: 10;
}

#fade-overlay {
	position: fixed;
	display: none;
	height: 100%;
	width: 100%;
	z-index: 9;
	background-color: rgba(0, 0, 0, 0.9);
}

#errorbox-container {
	display: table-cell;
	vertical-align: middle;
}

#errorbox {
	display: block;
	margin-left: auto;
	margin-right: auto;
	margin-top: 20px;
	margin-bottom: 20px;
	max-width: 800px;
	background-color: white;
	z-index: 11;
	font-size: 20px;
}

#errorbox-top {
	position: relative;
	display: block;
	width: 100%;
	height: 40px;
	background-color: ' . $maincolour . ';
	padding: 0px 20px 0px 20px;
	text-align: right;
	font-size: 20px;
	font-weight: 600;
	color: ' . $secondaryfontcolor . ';
	line-height: 40px;
}

#errorbox-top > a {
	font-weight: 600;
}

#errorbox-bottom {
	padding: 20px 20px 20px 20px;
	background-color: white;
	border-bottom: 1px solid ' . $maincolour . ';
	border-left: 1px solid ' . $maincolour . ';
	border-right: 1px solid ' . $maincolour . ';
}

#errorbox-content {
	color: red;
}

#errorbox-title {
	text-align: center;
	margin-bottom: 20px;
	font-weight: 400;
	font-size: 30px;
}

#table-container-1 {
	position: absolute;
	display: none;
	height: 100%;
	width: 100%;
	z-index: 13;
}

#fade-overlay-1 {
	position: fixed;
	display: none;
	height: 100%;
	width: 100%;
	z-index: 12;
	background-color: rgba(0, 0, 0, 0.9);
}

#errorbox-container-1 {
	display: table-cell;
	vertical-align: middle;
}

#errorbox-1 {
	display: block;
	margin-left: auto;
	margin-right: auto;
	margin-top: 20px;
	margin-bottom: 20px;
	max-width: 600px;
	background-color: white;
	z-index: 14;
	font-size: 20px;
}

#errorbox-top-1 {
	position: relative;
	display: block;
	width: 100%;
	height: 40px;
	background-color: ' . $maincolour . ';
	padding: 0px 20px 0px 20px;
	text-align: right;
	font-size: 20px;
	font-weight: 600;
	color: ' . $secondaryfontcolor . ';
	line-height: 40px;
}

#errorbox-top-1 > a {
	font-weight: 600;
}

#errorbox-bottom-1 {
	padding: 20px 20px 20px 20px;
	background-color: white;
	border-bottom: 1px solid ' . $maincolour . ';
	border-left: 1px solid ' . $maincolour . ';
	border-right: 1px solid ' . $maincolour . ';
}

#errorbox-content-1 {
	color: red;
}

#errorbox-title-1 {
	text-align: center;
	margin-bottom: 20px;
	font-weight: 400;
	font-size: 30px;
}

#table-container-2 {
	position: absolute;
	display: none;
	height: 100%;
	width: 100%;
	z-index: 16;
}

#fade-overlay-2 {
	position: fixed;
	display: none;
	height: 100%;
	width: 100%;
	z-index: 15;
	background-color: rgba(0, 0, 0, 0.9);
}

#errorbox-container-2 {
	display: table-cell;
	vertical-align: middle;
}

#errorbox-2 {
	display: block;
	margin-left: auto;
	margin-right: auto;
	margin-top: 20px;
	margin-bottom: 20px;
	max-width: 600px;
	background-color: white;
	z-index: 17;
	font-size: 20px;
}

#errorbox-top-2 {
	position: relative;
	display: block;
	width: 100%;
	height: 40px;
	background-color: ' . $maincolour . ';
	padding: 0px 20px 0px 20px;
	text-align: right;
	font-size: 20px;
	font-weight: 600;
	color: ' . $secondaryfontcolor . ';
	line-height: 40px;
}

#errorbox-top-2 > a {
	font-weight: 600;
}

#errorbox-bottom-2 {
	padding: 20px 20px 20px 20px;
	background-color: white;
	border-bottom: 1px solid ' . $maincolour . ';
	border-left: 1px solid ' . $maincolour . ';
	border-right: 1px solid ' . $maincolour . ';
}

#errorbox-content-2 {
	color: red;
}

#errorbox-title-2 {
	text-align: center;
	margin-bottom: 20px;
	font-weight: 400;
	font-size: 30px;
}

#top-navbar-container {
	position: relative;
	display: block;
	width: 100%;
	height: 50px;
	background-color: ' . $secondarycolour . ';
}

#top-navbar-left {
	position: relative;
	display: inline-block;
	margin-left: 20px;
	height: 50px;
}

#top-navbar-left-img {
	float: left;
	position: relative;
	display: inline-block;
	height: 40px;
	margin-top: 5px;
	line-height: 40px;
	font-size: 30px;
	color: ' . $secondaryfontcolor . ';
}

#top-navbar-left-list {
	display: inline-block;
	list-style-type: none;
	line-height: 50px;
	font-size: 20px;
	color: ' . $secondaryfontcolor . ';
	margin-left: 20px;
}

.top-navbar-left-button {
	display: inline-block;
	padding: 0px 10px 0px 10px;
	transition: background-color 0.5s ease;
	-webkit-transition: background-color 0.5s ease;
	-moz-transition: background-color 0.5s ease;
	-o-transition: background-color 0.5s ease;
	transition: background-color 0.5s ease;
	color: ' . $secondaryfontcolor . ';
}

.top-navbar-left-button:hover {
	background-color: ' . $maincolour . ';
}

.active {
	background-color: ' . $maincolour . ';
}

#top-navbar-right {
	float: right;
	position: relative;
	display: inline-block;
	height: 40px;
}

.top-navbar-right-button {
	display: inline-block;
	font-size: 20px;
	color: ' . $secondaryfontcolor . ';
	line-height: 50px;
	padding: 0px 10px 0px 10px;
	transition: background-color 0.5s ease;
	-webkit-transition: background-color 0.5s ease;
	-moz-transition: background-color 0.5s ease;
	-o-transition: background-color 0.5s ease;
	transition: background-color 0.5s ease;
}

.top-navbar-right-button:hover {
	background-color: ' . $maincolour . ';
}

#menu-button {
	position: relative;
	display: none;
	float: right;
	color: ' . $secondaryfontcolor . ';
	font-size: 30px;
	text-align: center;
	height: 50px;
	width: 50px;
	border-radius: 4px;
}

.top-navbar-dropdown {
	position:absolute;
	visibility:hidden;
	z-index: 1;
}

.top-navbar-dropdown > li {
	display:inline;
	float:none;
}

#hidden-list {
	position: relative;
	display: none;
	line-height: 50px;
	background-color: ' . $secondarycolour . ';
	color: ' . $secondaryfontcolor . ';
	list-style-type: none;
	font-size: 20px;
}

.hidden-list-button {
	padding-left: 20px;
}

#must-login {
	position: relative;
	display: block;
	margin: auto;
	text-align: center;
	font-size: 30px;
	margin-top: 50px;
}

#content-container {
	position: relative;
	display: block;

}

#welcome-container {
	position: relative;
	display: block;
	margin: 0px 20px 0px 20px;
	text-align: center;
}

#welcome-header {
	position: relative;
	display: block;
	font-size: 60px;
	font-weight: 900;
	margin: 30px 0px 30px 0px;
}

#welcome-message {
	font-size: 30px;
	margin-bottom: 50px;
}

#game-container {
	position: relative;
	display: block;
	margin: auto;
}

.game {
	position: relative;
	display: block;
	width: 300px;
	margin: auto;
	margin-bottom: 20px;
	padding: 50px 0px 50px 0px;
	border: 1px solid rgba(255,255,255,0);
	border-radius: 5px;
	transition: border-color 0.5s ease;
	-webkit-transition: border-color 0.5s ease;
	-moz-transition: border-color 0.5s ease;
	-o-transition: border-color 0.5s ease;
	transition: border-color 0.5s ease;
}

.game:hover {
	border: 1px solid ' . $maincolour . ';
}

.game-img {
	position: relative;
	display: block;
	width: 200px;
	height: 200px;
	margin: auto;');
	if ($circleImages == 1){
		print('
			border-radius: 100px;
			-webkit-border-radius: 100px;
			-moz-border-radius: 100px;
		');
	}
print('
    background-size: 100% auto;
    background-repeat: no-repeat;
  	background-position: center;
}

.game-name {
	text-align: center;
	margin-top: 20px;
	font-size: 30px;
	line-height: 30px;
	height: auto;
}

.package-price-info {
	text-align: center;
	margin-top: 20px;
	font-size: 30px;
	line-height: 30px;
	height: 30px;
}

#server-container {
	position: relative;
	display: block;
	margin: 50px 20px 0px 20px;
	text-align: center;
	font-size: 30px;
}

#account-container {
	position: relative;
	display: block;
	margin: 40px 20px 0px 20px;
}

#top-bar {
	height: 50px;
	max-width: 1200px;
	margin: auto;
}

#left-buttons {
	float: left;
}

#right-buttons {
	float: right;
}

#steam-avatar {
	position: relative;
	display: inline-block;
	width: 50px;
	height: 50px;
	border: 1px solid ' . $maincolour . ';
}

#steam-username {
	position: relative;
	display: inline;
	font-size: 30px;
	line-height: 50px;
	vertical-align: middle;
}

#logout-button {
	position: relative;
	display: block;
	float: right;
	height: 50px;
	font-size: 25px;
	padding: 0px 20px 0px 20px;
	border: 0;
	color: ' . $secondaryfontcolor . ';
	background-color: ' . $maincolour . ';
}

#account-info {
	position: relative;
	display: block;
	margin-top: 30px;
}

.statistics-box {
	position: relative;
	display: block;
	margin: auto;
	max-width: 1200px;
}

.statistics-title {
	background-color: ' . $maincolour . ';
	color: ' . $secondaryfontcolor . ';
	padding: 0px 10px 0px 10px;
	font-size: 25px;
	font-weight: 600;
}

.statistics-content {
	border: 1px solid ' . $maincolour . ';
	padding: 0px 10px 0px 10px;
	font-size: 20px;
	line-height: 40px;
}

#purchase-statistics {
	margin-top: 30px;
}

#credit-statistics {
	margin-top: 30px;
}

#purchase-list {
	margin-top: 30px;
	margin-bottom: 30px;
}

#dashboard-container {
	display: block;
	position: relative;
	margin: 40px 20px 0px 20px;
}

#side-navbar-container {
	vertical-align: top;
	display: block;
	float: left;
	position: relative;
	width: 250px;
	background-color: ' . $secondarycolour . ';
	border-top: 3px solid ' . $maincolour . ';
	border-bottom: 3px solid ' . $maincolour . ';
}

#side-navbar {
	list-style-type: none;
	color: ' . $secondaryfontcolor . ';
	line-height: 50px;
	font-size: 20px;
}

.side-navbar-button {
	padding: 0px 10px 0px 10px;
	background-color: ' . $secondarycolour . ';
	transition: background-color 0.5s ease;
	-webkit-transition: background-color 0.5s ease;
	-moz-transition: background-color 0.5s ease;
	-o-transition: background-color 0.5s ease;
	transition: background-color 0.5s ease;
}

.side-navbar-button:hover {
	background-color: ' . $maincolour . ';
}

#dashboard-content-container {
	position: relative;
	display: block;
	margin:0px 20px 0px 290px;
}

#dashboard-page-title {
	position: relative;
	display: block;
	margin-bottom: 30px;
	font-size: 40px;
	font-weight: 600;
}

.dashboard-stat-small {
	position: relative;
	display: block;
	margin: auto;
	margin-bottom: 30px;
}

.dashboard-stat-large {
	position: relative;
	display: block;
	margin: auto;
	margin-bottom: 30px;
}

.tooltip-btn {
	height: 30px;
	width: 30px;
	border-radius: 15px;
	margin-left: 10px;
	vertical-align: middle;
}

.tooltip {
	white-space: normal;
}

.settings-group {
	display: block;
	position: relative;
	margin-bottom: 40px;
}

.setting-title {
	font-size: 30px;
	clear: both;
}

.dashboard-radio-lbl {
	display: inline-block;
	font-size: 20px;
	margin: 0px 15px 20px 0px;
}

.settings-text-input {
	width: 100%;
	max-width: 800px;
	min-height: 35px;
	line-height: 35px;
	font-size: 20px;
	border: 1px solid ' . $maincolour . ';
}

.submit-button {
	display: block;
	position: relative;
	font-size: 20px;
	font-weight: 400;
	background-color: white;
	border: 1px solid ' . $maincolour . ';
	border-radius: 3px;
	margin-bottom: 20px;
	padding: 5px 10px 5px 10px;
}

.submit-button:hover {
	background-color: #E6E6E6;
}

.small-button {
	display: inline-block;
	float: right;
	text-align: center;
	line-height: 20px;
	height: 37px;
	width: 35px;
	margin: 0;
	cursor: pointer;
}

.small-button-glyphicon {
	display: block;
	margin-left: -3px;
	margin-top: -2px;
}

.command {
	 display: block;
	 margin-bottom: 5px;
}

#home-page-text-input {
	resize: vertical;
}

#packagedescriptioninput {
	resize: vertical;
}

#dashboard-menu-dropdown {
	display: none;
	margin-left: 20px;
}

.dropdown {
	display: inline-block;
	font-size: 20px;
	border: 1px solid ' . $maincolour . ';
	height: 40px;
}

#login-container {
	display: block;
	position: relative;
	margin-left: auto;
	margin-right: auto;
	margin-top: 50px;
	padding: 20px;
	max-width: 600px;
	border: 1px solid ' . $maincolour . ';
}

.g-recaptcha {
	display: block;
	position: relative;
	margin-bottom: 20px;
}

#steam-login-container {
	display: block;
	position: relative;
	width: 100%;
	text-align: center;
}

#steam-login-container-account {
	display: block;
	position: relative;
}

#graph-canvas {
	display: block;
	position: relative;
	width: 100%;
	height: 300px;
}

.graph-dropdown {
	margin: 10px;
}

#quill-wrapper {
	border: 1px ' . $maincolour . ' solid;
	border-radius: 3px;
}

#quill-toolbar {
	border-bottom: 1px ' . $maincolour . ' solid;
}

.user-choice-text {
	position: relative;
	display: inline-block;
	margin-left: 10px;
	font-size: 20px;
}

.choice-title {
	margin-bottom: 5px;
}

.package-price {
	font-size: 35px;
	margin-bottom: 30px;
	font-weight: 900;
	text-align: center;
}

.package-description {
	font-size: 25px;
	margin-bottom: 30px;
}

.buy-button {
	margin-left: auto;
	margin-right: auto;
	clear: both;
}

#total-price {
	text-align: center;
	padding-top: 20px;
}

#paypal-checkout-button {
	display: block;
	clear: both;
	margin-left: auto;
	margin-right: auto;
	padding-top: 20px;
}

#confirmation-title {
	text-align: center;
	font-size: 25px;
	margin-bottom: 20px;
}

.confirmation-listing {
	font-size: 25px;
}

#footer {
	display: block;
	position: absolute;
	bottom: 0;
	margin-top: 50px;
	height: 50px;
	width: 100%;
	background-color: ' . $secondarycolour . ';
}

.footer-text-left {
	float: left;
	margin-top: 10px;
	margin-left: 20px;
	font-size: 20px;
	color: ' . $secondaryfontcolor . ';
}

.footer-text-right {
	float: right;
	margin-top: 10px;
	margin-right: 20px;
	font-size: 20px;
	color: ' . $secondaryfontcolor . ';
}

.dropdown-button {
	width: 50px;
	text-align: center;
}

.dropdownmenu {
	position: relative;
    display: inline-block;
	margin-right: -4px;
}

.dropdowncontent {
	display: none;
    position: absolute;
	z-index: 2000;
}

.dropdowncontent a {
	background-color: ' . $secondarycolour . ';
    color: ' . $secondaryfontcolor . ';
	width: 50px;
	height: 50px;
	line-height: 50px;
	font-size: 20px;
    text-decoration: none;
	text-align: center;
    display: block;
	z-index: 2000;
}

.dropdowncontent a:hover {
	background-color: ' . $maincolour . ';
}

.show {
	display:block;
}


textarea {
	border: 1px solid ' . $maincolour . ';
	resize: vertical;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.loading-circle {
	margin-left: auto;
	margin-right: auto;
	border-radius: 50%;
    width: 50px;
    height: 50px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-top-color: ' . $maincolour . ';
    animation: spin 1s infinite linear;
}

');
