<?php
/**
 * $aOptions
 *
 *
 */
$aOptions[] = array(
	'iblock' 	=> '',
	'target' 	=> array( 'detail' => 'detail' ),
	'watermark' => '',
	'position'	=> 'rightbottom',
	'alpha'		=> 30,
);
if( !CModule::IncludeModule('iblock') ){
	echo 'Установите модуль "Инфоблоки"!';
	return;
}
?>
<script type="text/javascript">
if( typeof jQuery != 'undefined' ){
	jQuery(function(){
		jQuery( '.SimpleWatermark TABLE' ).not('.new').find( 'TR._2hide' ).hide();
		jQuery( '.SimpleWatermark TABLE' ).not('.new').find('TH.title').click(function(){
			jQuery('TR._2hide', jQuery(this).parents('TABLE:first')).toggle();
		})
	})
}
</script>
<form class="SimpleWatermark" name="SimpleWatermark" id="SimpleWatermark" method="post" action="" enctype="multipart/form-data">
	<?=bitrix_sessid_post()?>

	<style type="text/css">
		TABLE.list-table TR TH {
			background:#D4DAED;
			border: 1px solid #BDC6E0;
		}
		TABLE.list-table TR TH SPAN {
			cursor: pointer;
		}
	</style>
	<input type="submit" value="Сохранить" />
	<br /><br />
	<?foreach( $aOptions as $iKey => $aIBlock ):?>
	<?if( !empty($aIBlock['iblock']) ){
		$aIBlockInfo = CIBlock::GetByID( $aIBlock['iblock'] )->Fetch();
		if( $aIBlockInfo ){
			$sIBlockName = 'Инфоблок ['.$aIBlockInfo['ID'].']: '.$aIBlockInfo['NAME'];
		}
	}else{
		$sIBlockName = 'Новый инфоблок';
	}?>
	<table class="list-table <?if( empty($aIBlock['iblock']) ):?>new<?endif;?>" style="margin-bottom:40px;">
		<tr>
			<th class="title" colspan="2">
				<span alt="Свернуть/Развернуть" title="Свернуть/Развернуть"><?=$sIBlockName?></span>
			</th>
		</tr>
		<tr class="_2hide">
			<td width="25%">Инфоблок</td>
			<td>
				<?=BitrixGemsHelper::GetIBlockDropDownList( $aIBlock['iblock'], 'SimpleWatermark['.$iKey.'][iblock_type]', 'SimpleWatermark['.$iKey.'][iblock]' )?>
			</td>
		</tr>
		<tr class="_2hide">
			<td>Накладывать водяной знак на</td>
			<td>
				<input type="checkbox" name="SimpleWatermark[<?=$iKey?>][target][preview]" value="preview" <?if( isset( $aIBlock['target']['preview'] ) ) :?>checked="checked"<?endif;?> /> Картинку для анонса <br />
				<input type="checkbox" name="SimpleWatermark[<?=$iKey?>][target][detail]" value="detail" <?if( isset( $aIBlock['target']['detail'] ) ) :?>checked="checked"<?endif;?> /> Картинку для детальной новости
			</td>
		</tr>
		<tr class="_2hide">
			<td>Водяной знак (желательно использовать ТОЛЬКО PNG8)</td>
			<td>
				<input type="hidden" name="SimpleWatermark[<?=$iKey?>][watermark]" value="<?=$aIBlock['watermark']?>" />
				<input type="file" name="SimpleWatermark[watermark][<?=$iKey?>]" />
				<?if( !empty( $aIBlock['watermark'] ) ):?>
				<br /><img src="<?=str_replace( $_SERVER['DOCUMENT_ROOT'], '' ,$aIBlock['watermark'])?>" />
				<?endif;?>
			</td>
		</tr>
		<tr class="_2hide">
			<td>Размещение водяного знака</td>
			<td>
				<input type="radio" name="SimpleWatermark[<?=$iKey?>][position]" value="leftupper" <?if( $aIBlock['position'] == 'leftupper') :?>checked="checked"<?endif;?> /> Верхний левый угол <br />
				<input type="radio" name="SimpleWatermark[<?=$iKey?>][position]" value="rightupper" <?if( $aIBlock['position'] == 'rightupper') :?>checked="checked"<?endif;?>  /> Верхний правый угол <br />
				<input type="radio" name="SimpleWatermark[<?=$iKey?>][position]" value="leftbottom" <?if( $aIBlock['position'] == 'leftbottom') :?>checked="checked"<?endif;?> /> Нижний левый угол <br />
				<input type="radio" name="SimpleWatermark[<?=$iKey?>][position]" value="rightbottom" <?if( $aIBlock['position'] == 'rightbottom') :?>checked="checked"<?endif;?> /> Нижний правый угол
			</td>
		</tr>
		<tr class="_2hide">
			<td width="25%">Степень прозрачности водяного знака <br />(0 - абсолютно прозрачный, 100 - полностью непрозрачный )</td>
			<td>
				<input type="text" name="SimpleWatermark[<?=$iKey?>][alpha]" value="<?=htmlspecialchars($aIBlock['alpha'])?>" />
			</td>
		</tr>
		<tr class="_2hide">
			<th colspan="2">
				<?if( empty($aIBlock['iblock']) ):?>
					<input type="submit" value="Добавить"  />
				<?else:?>
					<input type="submit" value="Сохранить"  />
					<input type="button" value="Удалить" onclick="javascript: this.parentNode.parentNode.parentNode.parentNode.parentNode.removeChild( this.parentNode.parentNode.parentNode.parentNode );document.getElementById('SimpleWatermark').submit()" />
				<?endif;?>
			</th>
		</tr>
	</table>
	<?endforeach;?>
	<br /> <br />
	<input type="submit" value="Сохранить" />
</form>