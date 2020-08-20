<?php
class ControllerExtensionModuleOCNExtractingExtensions extends Controller {
	private $error = array ();
	private $dissallow_dir = array();
	
	public function install() {
		$this->load->model('setting/setting');
		$data = ['module_ocn_extracting_extensions_status' => 1];
		$this->model_setting_setting->editSetting('module_ocn_extracting_extensions', $data);
	}
	
	public function uninstall(){}

	public function index() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array (
			array (
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array (
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
			),
			array (
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/ocn_extracting_extensions', 'user_token=' . $this->session->data['user_token'], true),
			)
		);
		
		//Errors
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		$data['search'] = $this->url->link('extension/module/ocn_extracting_extensions/search', 'user_token=' . $this->session->data['user_token'] . '&page=modules', true);
		$data['files'] = $this->url->link('extension/module/ocn_extracting_extensions/files', 'user_token=' . $this->session->data['user_token'], true);
		$data['extract'] = $this->url->link('extension/module/ocn_extracting_extensions/extract', 'user_token=' . $this->session->data['user_token'], true);

		// View
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/module/ocn_extracting_extensions/ocn_extracting_extensions', $data));
	}
	
	public function search() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateModules()) {
			$data['success'] = $this->language->get('success');
			$data['module'] = (string)$this->request->post['module'];
			
			// @todo накер проверка на пост модуль сёрч???
			if (!isset ($this->request->post['module_search'])) {
				$directory = str_replace ('/admin/', '', DIR_APPLICATION);
				$this->dissallow_dir = array(
					DIR_IMAGE,
					DIR_STORAGE,
				);
				$data['module_search'] = $this->moduleSearch($directory, $directory, $data['module']);
				$data['module_total' ] = count ($data['module_search']);
			}
		} else {
			$data['error'] = $this->error;
		}
		
		$this->response->setOutput($this->load->view('extension/module/ocn_extracting_extensions/ocn_extracting_extensions_list', $data));
	}
	
	public function files() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateFiles()) {
			$data['success'] = $this->language->get('success');
			$data['module_list' ] = $this->moduleList();
			$data['module_total'] = count($data['module_list']);
			$data['remove'] = $this->url->link('extension/module/ocn_extracting_extensions/remove', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['error'] = $this->error;
		}
		
		$this->response->setOutput($this->load->view('extension/module/ocn_extracting_extensions/ocn_extracting_extensions_files', $data));
	}
	
	public function extract()
	{
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateExtract() && isset($this->request->post['module_search']) && is_array ($this->request->post['module_search'])) {
			$module_name = (string)$this->request->post['extract_module'];
			$this->moduleExtract($this->request->post['module_search'], $module_name);
			$data['success'] = str_replace('{name}', $module_name, $this->language->get('success_extract'));;
		} else {
			$data['error'] = $this->error;
		}
		
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($data));
	}
	
	public function remove()
	{
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateRemove() && isset($this->request->post['module_list']) && is_array ($this->request->post['module_list'])) {
			foreach ($this->request->post['module_list'] as $module) {
				if (file_exists ($module)) {
					unlink ($module);
				}
			}
			
			$data['success'] = $this->language->get('success_delete');
		} else {
			$data['error'] = $this->error;
		}
		
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($data));
	}
	
	protected function validateModules()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if ((utf8_strlen($this->request->post['module']) < 1)) {
			$this->error['module'] = $this->language->get('error_module');
		}
		
		return !$this->error;
	}
	
	protected function validateFiles()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions') && !$this->user->hasPermission('access', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		return !$this->error;
	}
	
	protected function validateExtract()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions') && !$this->user->hasPermission('access', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!class_exists('ZipArchive')) {
			$this->error['class_zip'] = $this->language->get('error_class_zip');
		}
		
		return !$this->error;
	}
	
	protected function validateRemove()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions') && !$this->user->hasPermission('access', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		return !$this->error;
	}

	public function download() {
		if (isset($this->request->get['file'])) {
			$file_zip = DIR_DOWNLOAD . 'ocn_extracting_extensions/' . $this->request->get['file'];
			if (is_file($file_zip)) {
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . $this->request->get['file'] . '"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file_zip));
				if (ob_get_level()) ob_end_clean();
				readfile($file_zip);
				exit;
			}

		}
	}
	
	private function moduleList() {
		$modules = array ();
		$files = glob ($this->validateDir() . '*');

		foreach ($files as $file) {
			$info = pathinfo ($file);
			$stat = stat ($file);

			$link = $this->url->link('extension/module/ocn_extracting_extensions/download', 'file=' . $info['basename'] . '&user_token=' . $this->session->data['user_token'], 'SSL');

			$modules[] = array (
				'file' => $file,
				'link' => $link,
				'name' => $info['basename'],
				'size' => round (($stat['size'] / 1024), 2),
				'date' => date ('d-m-Y H:i:s', $stat['ctime'])
			);
		}

		asort ($modules);
		return $modules;
	}
	
	private function moduleSearch($dir, $dir_, $module_name) {
		static $files = array ();
		$s = '/';

		if (is_dir($dir) && $handle = opendir ($dir)) {
			while (FALSE !== ($file = readdir ($handle))) {
				if ($file[0] != '.') {
					$f_name = $dir . $s . $file . $s;
					if (in_array($f_name, $this->dissallow_dir)) {
						continue;
					}
					$f_name = $dir . $s . $file;
					if (is_dir ($f_name)) {
						$files = array_merge ($this->moduleSearch($f_name, $dir_, $module_name), $files);
					} elseif (preg_match ('/'. $module_name . '/', $file)) {
						$info = pathinfo ($dir . $s . $file);
						if (!isset ($files[$info['filename']])) {
							$files[$info['filename']] = array ('module' => $info['filename'], 'files' => array ());
						}
						$files[$info['filename']]['files'][] = array (
							'name' => $dir . $s . $file,
							'path' => str_replace ($dir_, FALSE, $dir),
							'file' => $file
						);
					}
				}
			}

			closedir($handle);
		}

		return $files;
	}
	
	private function moduleExtract ($module_search, $module_name) {
		$dir = $this->validateDir() . $module_name . '.' . time() . '.' . md5 ($module_name) . '.zip';
		$zip = new ZipArchive();
		if ($zip->open($dir, ZIPARCHIVE::CREATE) !== true) {
			$this->error['warning'] = $this->language->get('error_creat_zip');
		}

		$dir = explode('/', DIR_APPLICATION);
		$dir = array_splice($dir,0,-2);
		array_shift($dir);
		$directory = implode('/',$dir);

		foreach ($module_search as $modules) {
			$dir_zip = FALSE;
			$info    = pathinfo ($modules);
			$folders = explode ('/', $info['dirname']);

			for ($i = 1; $i < count ($folders); $i++) {
				$dir_zip .= $folders[$i] . '/';
			}
			$dir_replace = 'upload' . str_replace($directory,'', $dir_zip);
			$zip->addFile($modules, $dir_replace . $info['basename']);
		}
		$zip->close();
	}

	private function validateDir() {
		$dir = DIR_DOWNLOAD . '/ocn_extracting_extensions';
		if (!is_dir ($dir)) {
			mkdir ($dir);
		}
		return $dir . '/';
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
