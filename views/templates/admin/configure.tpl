{*
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<div class="row moduleconfig-header">
		<div class="col-xs-12">
			<img src="{$module_dir|escape:'html':'UTF-8'}logo.png" />
		</div>		
	</div>
	<hr />
	<div class="moduleconfig-content">
		<div class="row">
			<div class="col-xs-12">
				<p class="h3">
					{l s='This module lets you to create products on PrestaShop uploading a csv file' mod='uploadproductsfromfile'}
				</p>			
				<p class="h4">
					{l s='Be sure that the file is in the following format' mod='uploadproductsfromfile'}:
					{l s='First row with the Fileds: ' mod='uploadproductsfromfile'}<br />
					<span class="text-primary">{l s='Name, Reference, EAN13, Cost price, Retail price, Tax rate, Quantity, Categories (each category separated by ;), Manufacturer' mod='uploadproductsfromfile'}</span>
				</p>
			</div>
		</div>
	</div>
</div>