<?php
/**
 * Подключаем jQuery :)
 *
 * @author		Vladimir Savenkov <iVariable@gmail.com>
 *
 */
class BitrixGem_SimpleWatermark extends BaseBitrixGem{

	protected $aGemInfo = array(
		'GEM'			=> 'SimpleWatermark',
		'AUTHOR'		=> 'Владимир Савенков',
		'AUTHOR_LINK'	=> 'http://bitrixgems.ru/',
		'DATE'			=> '14.02.2011',
		'VERSION'		=> '0.1',
		'NAME' 			=> 'SimpleWatermark',
		'DESCRIPTION' 	=> "Простое автоматическое наложение водяного знака на картинки анонса и полного описания инфоблоков. PNG24 в качестве водяного знака не поддерживается. Доступен выбор расположения водяного знака и уровня прозрачности.",
		'DESCRIPTION_FULL' => 'Сделан минимальный набор необходимого функционала. Потом можно добавить красивостей на вотермарк, масшатбирование и т.п. Пока что этим заниматься некогда.',
		'CHANGELOG'		=> '',
		'REQUIREMENTS'	=> "jQuery желателен (без него тоже будет работать, но менее удобно), \n GD2 для PHP (минимальное требование Битрикс.)",
		'REQUIRED_MODULES' => array( 'iblock' ),
	);

	public function initGem(){
		AddEventHandler(
			'iblock',
			'OnBeforeIBlockElementAdd',
			array( $this, 'processRequest' ),
			10000
		);
		AddEventHandler(
			'iblock',
			'OnBeforeIBlockElementUpdate',
			array( $this, 'processRequest' ),
			10000
		);
	}

	public function checkRequirements(){
		$oModule = CModule::CreateModuleObject('iv.bitrixgems');
		if( $oModule->MODULE_VERSION < '1.0.3' ) throw new Exception('Для работы гема необходима 1.0.3 версия модуля. У вас установлена версия '.$oModule->MODULE_VERSION.'. Пожалуйста, установите обновление.');
	}
	
	public function processRequest( &$arFields ){
		if( !isset( $arFields['IBLOCK_ID'] ) ) return true;
		$aOptions = $this->getOptions();
		foreach( $aOptions as $aIBlock ){
			if( $aIBlock['iblock'] == $arFields['IBLOCK_ID'] ){
				if( isset( $aIBlock['target']['preview'] ) && isset( $arFields['PREVIEW_PICTURE']['tmp_name'] ) ){
					$this->makeWatermarkedImage(
						$arFields['PREVIEW_PICTURE']['tmp_name'],
						$aIBlock['watermark'],
						$arFields['PREVIEW_PICTURE']['tmp_name'],
						$aIBlock['position'],
						$aIBlock['alpha']
					);
				}
				if( isset( $aIBlock['target']['detail'] ) && isset( $arFields['DETAIL_PICTURE']['tmp_name'] ) ){
					$this->makeWatermarkedImage(
						$arFields['DETAIL_PICTURE']['tmp_name'],
						$aIBlock['watermark'],
						$arFields['DETAIL_PICTURE']['tmp_name'],
						$aIBlock['position'],
						$aIBlock['alpha']
					);
				}
			}
		}
		return true;
	}
	
	
	
	public function needAdminPage(){
		return true;
	}
	public function showAdminPage(){
		$aOptions = $this->getOptions();	
		require_once( dirname(__FILE__).'/options/adminOptionPage.php' );
	}
	public function processAdminPage( $aOptions ){
		if( !empty( $aOptions['SimpleWatermark'] ) ){
			$this->setOptions( $aOptions['SimpleWatermark'] );
			$this->addMessage( 'Сохранено!' );
		}
				
	}
	
	protected function getOptions(){
		$aOptions = array();
		if( is_readable( dirname(__FILE__).'/options/options.php') ) $aOptions = include( dirname(__FILE__).'/options/options.php' );
		if( !is_array( $aOptions ) ) $aOptions = array();
		return $aOptions;
	}

	protected function setOptions( $aOptions ){
		$a2SaveOptions = array();
		
		foreach( $aOptions as $iOptionKey => $aOpt ){
			if( empty( $aOpt['iblock'] ) || empty( $aOpt['target'] ) || empty( $aOpt['position'] ) ) continue;
			if( !in_array( $aOpt['position'], array( 'leftupper', 'rightupper', 'leftbottom', 'rightbottom' ) ) ) $aOpt['position'] = 'rightbottom';
						
			$aTOptions = array(
				'iblock' 	=> @$aOpt['iblock'],
				'target' 	=> @$aOpt['target'],
				'watermark' => @$aOpt['watermark'],
				'position' 	=> @$aOpt['position'],
				'alpha'		=> (int)$aOpt['alpha'],
			);
			
			if( $aTOptions['alpha'] <= 0 || $aTOptions['alpha'] > 100 ) $aTOptions['alpha'] = 90;
			
			if( !empty( $_FILES['SimpleWatermark']['tmp_name']['watermark'][ $iOptionKey ] ) ){
				/**
				 * Тут бы по хорошему использовать $this->getGemFolder() , но так как у меня симлинки, то приходит ай-ай-ай.
				 */
				$sNewFilePath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iv.bitrixgems/gems/'.$this->getName().'/watermarks/'.$_FILES['SimpleWatermark']['name']['watermark'][ $iOptionKey ];
				move_uploaded_file( $_FILES['SimpleWatermark']['tmp_name']['watermark'][ $iOptionKey ], $sNewFilePath );
				if( is_readable( $sNewFilePath ) ) $aTOptions['watermark'] = $sNewFilePath;
			}			
			if( empty( $aTOptions['watermark'] ) ) throw new Exception('Не выбран водяной знак!');
			$a2SaveOptions[] = $aTOptions;
		}
		

		return file_put_contents(
			dirname(__FILE__).'/options/options.php',
			'<?php return '.var_export( $a2SaveOptions, true ).';?>'
		);
	}
	
	protected function makeWatermarkedImage( $sSourceFile, $sWatermarkFile, $sWatermarkedImageFile, $sPosition = 'rightbottom' , $iAlphaLevel = 90, $iOutputQuality = 90 ){
		
		$iOutputQuality = (int)$iOutputQuality;
		if( $iOutputQuality > 100 || $iOutputQuality <= 0 ) $iOutputQuality = 90;
		
		$iAlphaLevel = (int)$iAlphaLevel;
		if( $iAlphaLevel > 100 || $iAlphaLevel <= 0 ) $iAlphaLevel = 90;
		
		list( $iSourceWidth, $iSourceHeight, $mSourceType ) = getimagesize( $sSourceFile );
		if ( $mSourceType === NULL ) return false;
  		switch ( $mSourceType ){
  			
			case IMAGETYPE_GIF:
				$oSourceGDImage = imagecreatefromgif( $sSourceFile );
    			break;
    			
			case IMAGETYPE_JPEG:
				$oSourceGDImage = imagecreatefromjpeg( $sSourceFile );
				break;
				
			case IMAGETYPE_PNG:
				$oSourceGDImage = imagecreatefrompng( $sSourceFile );
				break;
			default:
    			return false;
		}

		list( $iWatermarkWidth, $iWatermarkHeight, $mWatermarkType ) = getimagesize( $sWatermarkFile );
		if ( $mWatermarkType === NULL ) return false;
  		switch ( $mWatermarkType ){
  			
			case IMAGETYPE_GIF:
				$oWatermarkGDImage = imagecreatefromgif( $sWatermarkFile );
    			break;
    			
			case IMAGETYPE_JPEG:
				$oWatermarkGDImage = imagecreatefromjpeg( $sWatermarkFile );
				break;
				
			case IMAGETYPE_PNG:
				$oWatermarkGDImage = imagecreatefrompng( $sWatermarkFile );
				break;
			default:
    			return false;
		}
		
		switch( $sPosition ){
			
			case 'leftupper':
				imagecopymerge(
					$oSourceGDImage,
					$oWatermarkGDImage,
					0,
					0,
					0,
					0,
					$iWatermarkWidth,
					$iWatermarkHeight,
					$iAlphaLevel
				);
				break;
				
			case 'rightupper':
				imagecopymerge(
					$oSourceGDImage,
					$oWatermarkGDImage,
					$iSourceWidth - $iWatermarkWidth,
					0,
					0,
					0,
					$iWatermarkWidth,
					$iWatermarkHeight,
					$iAlphaLevel
				);
				break;
				
			case 'leftbottom':
				imagecopymerge(
					$oSourceGDImage,
					$oWatermarkGDImage,
					0,
					$iSourceHeight - $iWatermarkHeight,
					0,
					0,
					$iWatermarkWidth,
					$iWatermarkHeight,
					$iAlphaLevel
				);
				break;
				
			default:
			case 'rightbottom':
				imagecopymerge(
					$oSourceGDImage,
					$oWatermarkGDImage,
					$iSourceWidth - $iWatermarkWidth,
					$iSourceHeight - $iWatermarkHeight,
					0,
					0,
					$iWatermarkWidth,
					$iWatermarkHeight,
					$iAlphaLevel
				);
				break;
			
		}

		imagejpeg( $oSourceGDImage, $sWatermarkedImageFile, $iOutputQuality );

		imagedestroy( $oSourceGDImage );
		imagedestroy( $oWatermarkGDImage );
		return true;
	}
	
}
?>