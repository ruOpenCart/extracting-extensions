<?php
class ControllerExtensionModuleOCNExtractingExtensions extends Controller {
	private $error = [];
	private $disallow_dir = [];
	
	public function install() {
		$this->load->model('setting/setting');
		$data = ['module_ocn_extracting_extensions_status' => 1];
		$this->model_setting_setting->editSetting('module_ocn_extracting_extensions', $data);
	}
	
	public function uninstall() {}

	public function index() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		$this->document->setTitle($this->language->get('heading_title'));

		// BreadCrumbs
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
		if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];
			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['error_download'])) {
			$data['error_download'] = $this->session->data['error_download'];
			unset($this->session->data['error_download']);
		} else {
			$data['error_download'] = '';
		}
		
		// Urls
		$data['url_cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		$data['url_search'] = $this->url->link('extension/module/ocn_extracting_extensions/search', 'user_token=' . $this->session->data['user_token'], true);
		$data['url_files'] = $this->url->link('extension/module/ocn_extracting_extensions/files', 'user_token=' . $this->session->data['user_token'], true);
		$data['url_extract'] = $this->url->link('extension/module/ocn_extracting_extensions/extract', 'user_token=' . $this->session->data['user_token'], true);
		$data['url_remove'] = $this->url->link('extension/module/ocn_extracting_extensions/remove', 'user_token=' . $this->session->data['user_token'], true);

		// View
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/module/ocn_extracting_extensions/ocn_extracting_extensions', $data));
	}
	
	public function search() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateSearchModules()) {
			$data['url_extract'] = $this->url->link('extension/module/ocn_extracting_extensions/extract', 'user_token=' . $this->session->data['user_token'], true);
			$data['success'] = $this->language->get('success');
			$data['module_name'] = (string)$this->request->post['module_name'];
			$directory = str_replace ('/admin/', '', DIR_APPLICATION);
			$this->disallow_dir = [
				DIR_IMAGE,
				DIR_STORAGE
			];
			$data['modules'] = $this->searchModules($directory, $directory, $data['module_name']);
			$data['total' ] = count($data['modules']);
		} else {
			if (isset($this->error['warning'])) {
				$data['error_warning'] = $this->error['warning'];
			} else {
				$data['error_warning'] = '';
			}
			if (isset($this->error['module'])) {
				$data['error_module'] = $this->error['module'];
			} else {
				$data['error_module'] = '';
			}
		}
		
		$this->response->setOutput($this->load->view('extension/module/ocn_extracting_extensions/ocn_extracting_extensions_list', $data));
	}
	
	protected function validateSearchModules() {
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if ((utf8_strlen($this->request->post['module_name']) < 5)) {
			$this->error['module'] = $this->language->get('error_name_min');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('warning');
		}
		
		return !$this->error;
	}
	
	private function searchModules($dir, $dir_, $module_name) {
		static $files = [];
		$s = '/';

		if (is_dir($dir) && $handle = opendir($dir)) {
			while (FALSE !== ($file = readdir($handle))) {
				if ($file[0] != '.') {
					$f_name = $dir . $s . $file . $s;
					if (in_array($f_name, $this->disallow_dir)) {
						continue;
					}
					$f_name = $dir . $s . $file;
					if (is_dir ($f_name)) {
						$files = array_merge($this->searchModules($f_name, $dir_, $module_name), $files);
					} elseif (preg_match ('/' . $module_name . '/', $file)) {
						$info = pathinfo ($dir . $s . $file);
						if (!isset($files[$info['filename']])) {
							$files[$info['filename']] = ['module' => $info['filename'], 'files' => []];
						}
						$files[$info['filename']]['files'][] = [
							'name' => $dir . $s . $file,
							'path' => str_replace ($dir_, FALSE, $dir),
							'file' => $file
						];
					}
				}
			}
			closedir($handle);
		}

		return $files;
	}
	
	public function extract() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateExtractModules()) {
			$module_name = (string)$this->request->post['extract_module'];
			$this->extractModules($this->request->post['modules'], $module_name);
			$data['success'] = $this->language->get('success');
			$data['success_text'] = str_replace('{name}', $module_name, $this->language->get('success_extract'));
		} else {
			if (isset($this->error['warning'])) {
				$data['errors']['error_warning'] = $this->error['warning'];
			}
			if (isset($this->error['zip'])) {
				$data['errors']['error_zip'] = $this->error['zip'];
			}
			if (isset($this->error['extract'])) {
				$data['errors']['error_extract'] = $this->error['extract'];
			}
		}
		
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($data));
	}
	
	protected function validateExtractModules() {
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions') || !$this->user->hasPermission('access', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!class_exists('ZipArchive')) {
			$this->error['zip'] = $this->language->get('error_class_zip');
		}
		
		if (!isset($this->request->post['modules']) || !is_array($this->request->post['modules'])) {
			$this->error['extract'] = $this->language->get('error_extract');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('warning');
		}
		
		return !$this->error;
	}
	
	private function extractModules($modules, $module_name) {
		$dir = $this->validateDir() . $module_name . '.' . time() . '.' . md5 ($module_name) . '.zip';
		$zip = new ZipArchive();
		if ($zip->open($dir, ZIPARCHIVE::CREATE) !== true) {
			$this->error['warning'] = $this->language->get('error_create_zip');
		}

		$dir = explode('/', DIR_APPLICATION);
		$dir = array_splice($dir,0,-2);
		array_shift($dir);
		$directory = implode('/',$dir);

		foreach ($modules as $modules_list) {
			$dir_zip = FALSE;
			$info    = pathinfo ($modules_list);
			$folders = explode ('/', $info['dirname']);

			for ($i = 1; $i < count($folders); $i++) {
				$dir_zip .= $folders[$i] . '/';
			}
			$dir_replace = 'upload' . str_replace($directory, '', $dir_zip);
			$zip->addFile($modules_list, $dir_replace . $info['basename']);
		}
		$zip->close();
	}
	
	public function files() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateGetFiles()) {
			$data['url_remove'] = $this->url->link('extension/module/ocn_extracting_extensions/remove', 'user_token=' . $this->session->data['user_token'], true);
			$data['success'] = $this->language->get('success');
			$data['files' ] = $this->getFiles();
			$data['total'] = count($data['files']);
		} else {
			if (isset($this->error['warning'])) {
				$data['error_warning'] = $this->error['warning'];
			} else {
				$data['error_warning'] = '';
			}
		}

		$this->response->setOutput($this->load->view('extension/module/ocn_extracting_extensions/ocn_extracting_extensions_files', $data));
	}
	
	protected function validateGetFiles() {
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions') || !$this->user->hasPermission('access', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('warning');
		}
		
		return !$this->error;
	}
	
	private function getFiles() {
		$modules = [];
		$files = glob($this->validateDir() . '*');

		foreach ($files as $file) {
			$info = pathinfo ($file);
			$stat = stat ($file);

			$link = $this->url->link('extension/module/ocn_extracting_extensions/download', 'file=' . $info['basename'] . '&user_token=' . $this->session->data['user_token'], true);

			$modules[] = [
				'file' => $file,
				'link' => $link,
				'name' => $info['basename'],
				'size' => round (($stat['size'] / 1024), 2),
				'date' => date ('d-m-Y H:i:s', $stat['ctime'])
			];
		}
		asort($modules);

		return $modules;
	}
	
	public function remove() {
		$this->load->language('extension/module/ocn_extracting_extensions');
		
		if ($this->validateRemoveFiles()) {
			foreach ($this->request->post['files'] as $module) {
				if (file_exists ($module)) {
					unlink ($module);
				}
			}
			$data['success'] = $this->language->get('success_delete');
			$data['status'] = $this->language->get('success');
		} else {
			if (isset($this->error['warning'])) {
				$data['error_warning'] = $this->error['warning'];
			} else {
				$data['error_warning'] = '';
			}
			if (isset($this->error['delete'])) {
				$data['error_delete'] = $this->error['delete'];
			} else {
				$data['error_delete'] = '';
			}
		}
		
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($data));
	}
	
	protected function validateRemoveFiles() {
		if (!$this->user->hasPermission('modify', 'extension/module/ocn_extracting_extensions') || !$this->user->hasPermission('access', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->post['files']) || !is_array($this->request->post['files'])) {
			$this->error['delete'] = $this->language->get('error_select_delete');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('warning');
		}
		
		return !$this->error;
	}

	public function download() {
		$this->load->language('extension/module/ocn_extracting_extensions');

		if ($this->validateDownloadFiles()) {
			$file_zip = DIR_DOWNLOAD . 'ocn_extracting_extensions/' . $this->request->get['file'];

			$this->response->addheader('Pragma: public');
			$this->response->addheader('Expires: 0');
			$this->response->addheader('Content-Description: File Transfer');
			$this->response->addheader('Content-Type: application/octet-stream');
			$this->response->addheader('Content-Disposition: attachment; filename="' . $this->request->get['file'] . '"');
			$this->response->addheader('Content-Transfer-Encoding: binary');
			$this->response->addheader('Content-Length: ' . filesize($file_zip));

			$this->response->setOutput(file_get_contents($file_zip, FILE_USE_INCLUDE_PATH, null));
		} else {
			$this->session->data['error'] = $this->error;
			if (isset($this->error['warning'])) {
				$this->session->data['error_warning'] = $this->error['warning'];
			}
			if (isset($this->error['download'])) {
				$this->session->data['error_download'] = $this->error['download'];
			}

			$this->response->redirect($this->url->link('extension/module/ocn_extracting_extensions', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	protected function validateDownloadFiles() {
		if (!$this->user->hasPermission('access', 'extension/module/ocn_extracting_extensions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->get['file'])) {
			$this->error['download'] = $this->language->get('error_download_file');
		} else {
			$download_file = DIR_DOWNLOAD . 'ocn_extracting_extensions/' . $this->request->get['file'];
			if (!is_file($download_file) || !file_exists($download_file)) {
				$this->error['download'] = $this->language->get('error_download_not');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('warning');
		}
		
		return !$this->error;
	}

	private function validateDir() {
		$dir = DIR_DOWNLOAD . '/ocn_extracting_extensions';
		if (!is_dir($dir)) {
			mkdir($dir);
		}

		return $dir . '/';
	}
}
