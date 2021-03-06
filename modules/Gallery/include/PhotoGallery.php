<?php
/**
 *  This file is part of Dashboard.
 *
 *  Dashboard is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Dashboard is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Dashboard.  If not, see <http://www.gnu.org/licenses/>.
 *  
 *  @license http://www.gnu.org/licenses/gpl.txt
 *  @copyright Copyright 2007-2009 Norex Core Web Development
 *  @author See CREDITS file
 *
 */

class PhotoGallery extends DBRow {
	
	function createTable() {
		$cols = array(
			'id?',
			DBColumn::make('text', 'name', 'Photo Gallery Name'),
			DBColumn::make('PhotoGallery(name)', 'parent_gallery_id', 'Parent Photo Gallery'),
			DBColumn::make('File(description)', 'thumbnail_id', 'Thumbnail Image'),
			DBColumn::make('textarea', 'description', 'Description', array ('rows' => 16, 'cols' => 50)),
			'timestamp',
			'//status',
			);
		return parent::createTable("photo_galleries", __CLASS__, $cols);
	}
	static function getAll() {
		$args = func_get_args();
		array_unshift($args, __CLASS__);
		return call_user_func_array(array('DBRow', 'getAllRows'), $args);
	}
	static function countAll() {
		$args = func_get_args();
		array_unshift($args, __CLASS__);
		return call_user_func_array(array('DBRow', 'getCountRows'), $args);
	}
	function quickformPrefix() {return 'photo_gallery_';}
	
	public function getSubGalleries() {
		return self::getAll('where parent_gallery_id=?', 'i', $this->get('id'));
	}
	
	public function getGalleryImages() {
		return PhotoGalleryImage::getAll('where photo_gallery_id=?', 'i', $this->get('id'));
	}
	
	public function getFirstImage(){
		$images = PhotoGalleryImage::getAll('where photo_gallery_id=? limit 1 ' , 'i', $this->get('id'));
		if (!isset($images[0])){
			return false;
		}
		return $images[0]->get('file');
	}
}

DBRow::init('PhotoGallery');
