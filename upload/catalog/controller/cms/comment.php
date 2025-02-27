<?php
namespace Opencart\Catalog\Controller\Cms;
/**
 * Class Comment
 *
 * @package Opencart\Catalog\Controller\Cms
 */
class Comment extends \Opencart\System\Engine\Controller {
	/**
	 * @return string
	 */
	public function index(): string {
		$this->load->language('cms/comment');

		if (isset($this->request->get['article_id'])) {
			$data['article_id'] = (int)$this->request->get['article_id'];
		} else {
			$data['article_id'] = 0;
		}

		if (isset($this->request->get['sort']) && $this->request->get['route'] == 'cms/blog.info') {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'date_added';
		}

		if (isset($this->request->get['order']) && $this->request->get['route'] == 'cms/blog.info') {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		$this->load->model('cms/article');

		$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->model_cms_article->getTotalComments($data['article_id'], ['parent_id' => 0]));

		$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', 'language=' . $this->config->get('config_language')), $this->url->link('account/register', 'language=' . $this->config->get('config_language')));

		$data['comment_guest'] = ($this->customer->isLogged() || $this->config->get('config_comment_guest') ? true : false);

		// Create a login token to prevent brute force attacks
		$data['comment_add'] = $this->url->link('cms/comment.add', 'language=' . $this->config->get('config_language') . '&article_id=' . $data['article_id'] . '&comment_token=' . $this->session->data['comment_token'] = oc_token(32), true);

		$data['like'] = $this->url->link('cms/comment.rating', 'language=' . $this->config->get('config_language') . '&article_id=' . $data['article_id'] . '&rate=1&comment_token=' . $this->session->data['comment_token'], true);
		$data['dislike'] = $this->url->link('cms/comment.rating', 'language=' . $this->config->get('config_language') . '&article_id=' . $data['article_id'] . '&rate=0&comment_token=' . $this->session->data['comment_token'], true);

		$data['list'] = $this->controller_cms_comment->getList();

		$data['sorts'] = [];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_date_added_asc'),
			'value' => 'date_added-ASC',
			'href'  => $this->url->link('cms/comment.list', 'language=' . $this->config->get('config_language') . '&sort=date_added&order=ASC')
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_date_added_desc'),
			'value' => 'date_added-DESC',
			'href'  => $this->url->link('cms/comment.list', 'language=' . $this->config->get('config_language') . '&sort=date_added&order=DESC')
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_rating_asc'),
			'value' => 'rating-ASC',
			'href'  => $this->url->link('cms/comment.list', 'language=' . $this->config->get('config_language') . '&sort=rating&order=ASC')
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_rating_desc'),
			'value' => 'rating-DESC',
			'href'  => $this->url->link('cms/comment.list', 'language=' . $this->config->get('config_language') . '&sort=rating&order=DESC')
		];

		$data['sort'] = $sort;
		$data['order'] = $order;

		// Captcha
		$this->load->model('setting/extension');

		$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

		if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('comment', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code']);
		} else {
			$data['captcha'] = '';
		}

		$data['language'] = $this->config->get('config_language');

		return $this->load->view('cms/comment', $data);
	}

	/**
	 * @return void
	 */
	public function list(): void {
		$this->load->language('cms/comment');

		$this->response->setOutput($this->controller_cms_comment->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		if (isset($this->request->get['article_id'])) {
			$article_id = (int)$this->request->get['article_id'];
		} else {
			$article_id = 0;
		}

		if (isset($this->request->get['sort'])) {
			$sort = (string)$this->request->get['sort'];
		} else {
			$sort = 'date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = (string)$this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if ($this->customer->isLogged() || $this->config->get('config_comment_guest')) {
			$data['comment_guest'] = true;
		} else {
			$data['comment_guest'] = false;
		}

		$limit = 5;

		$data['comments'] = [];

		$filter_data = [
			'parent_id' => 0,
			'sort'      => $sort,
			'order'     => $order,
			'start'     => ($page - 1) * $limit,
			'limit'     => $limit
		];

		$this->load->model('cms/article');

		$results = $this->model_cms_article->getComments($article_id, $filter_data);

		foreach ($results as $result) {
			$reply_total = $this->model_cms_article->getTotalComments($article_id, ['parent_id' => $result['article_comment_id']]);

			if ($reply_total) {
				$reply = $this->url->link('cms/comment.reply', 'language=' . $this->config->get('config_language') . '&article_id=' . $article_id . '&parent_id=' . $result['article_comment_id'], true);
			} else {
				$reply = '';
			}

			$data['comments'][] = [
				'article_comment_id' => $result['article_comment_id'],
				'comment'            => nl2br($result['comment']),
				'author'             => $result['author'],
				'date_added'         => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'like'               => $this->url->link('cms/comment.rating', 'language=' . $this->config->get('config_language') . '&article_id=' . $article_id . '&article_comment_id=' . $result['article_comment_id'] . '&rate=1&comment_token=' . $this->session->data['comment_token'], true),
				'dislike'            => $this->url->link('cms/comment.rating', 'language=' . $this->config->get('config_language') . '&article_id=' . $article_id . '&article_comment_id=' . $result['article_comment_id'] . '&rate=0&comment_token=' . $this->session->data['comment_token'], true),
				'reply'              => $reply,
				'reply_add'          => $this->url->link('cms/comment.add', 'language=' . $this->config->get('config_language') . '&comment_token=' . $this->session->data['comment_token'] . '&article_id=' . $article_id . '&parent_id=' . $result['article_comment_id'], true),
				'reply_total'        => $reply_total

			];
		}

		$comment_total = $this->model_cms_article->getTotalComments($article_id, $filter_data);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $comment_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('cms/comment.list', 'language=' . $this->config->get('config_language') . '&article_id=' . $article_id . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($comment_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($comment_total - $limit)) ? $comment_total : ((($page - 1) * $limit) + $limit), $comment_total, ceil($comment_total / $limit));

		return $this->load->view('cms/comment_list', $data);
	}

	/**
	 * @return void
	 */
	public function reply(): void {
		$this->load->language('cms/comment');

		$this->response->setOutput($this->controller_cms_comment->getReplies());
	}

	/**
	 * Reply
	 *
	 * @return string
	 */
	public function getReplies(): string {
		if (isset($this->request->get['article_id'])) {
			$article_id = (int)$this->request->get['article_id'];
		} else {
			$article_id = 0;
		}

		if (isset($this->request->get['parent_id'])) {
			$parent_id = (int)$this->request->get['parent_id'];
		} else {
			$parent_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if ($this->customer->isLogged() || $this->config->get('config_comment_guest')) {
			$data['comment_guest'] = true;
		} else {
			$data['comment_guest'] = false;
		}

		$limit = 5;

		$data['replies'] = [];

		$filter_data = [
			'parent_id' => $parent_id,
			'sort'      => 'date_added',
			'order'     => 'ASC',
			'start'     => ($page - 1) * $limit,
			'limit'     => $limit
		];

		$this->load->model('cms/article');

		$results = $this->model_cms_article->getComments($article_id, $filter_data);

		foreach ($results as $result) {
			$data['replies'][] = [
				'article_comment_id' => $result['article_comment_id'],
				'parent_id'          => $result['parent_id'],
				'comment'            => nl2br($result['comment']),
				'author'             => $result['author'],
				'date_added'         => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			];
		}

		$reply_total = $this->model_cms_article->getTotalComments($article_id, $filter_data);

		$data['parent_id'] = $parent_id;
		$data['page'] = $page;

		$data['refresh'] = $this->url->link('cms/comment.reply', 'language=' . $this->config->get('config_language') . '&article_id=' . $article_id . '&parent_id=' . $parent_id . '&page=' . $page, true);

		if (($page * $limit) < $reply_total) {
			$data['next'] = $this->url->link('cms/comment.reply', 'language=' . $this->config->get('config_language') . '&article_id=' . $article_id . '&parent_id=' . $parent_id . '&page=' . ($page + 1), true);
		} else {
			$data['next'] = '';
		}

		return $this->load->view('cms/comment_reply', $data);
	}

	/*
	 * Add
	 *
     * @return void
     */
	public function add(): void {
		$this->load->language('cms/comment');

		$json = [];

		if (isset($this->request->get['article_id'])) {
			$article_id = (int)$this->request->get['article_id'];
		} else {
			$article_id = 0;
		}

		if (isset($this->request->get['parent_id'])) {
			$parent_id = (int)$this->request->get['parent_id'];
		} else {
			$parent_id = 0;
		}

		if (!isset($this->request->get['comment_token']) || !isset($this->session->data['comment_token']) || $this->request->get['comment_token'] != $this->session->data['comment_token']) {
			$json['error']['warning'] = $this->language->get('error_token');
		}

		$keys = [
			'author',
			'comment'
		];

		foreach ($keys as $key) {
			if (!isset($this->request->post[$key])) {
				$this->request->post[$key] = '';
			}
		}

		if (!$this->config->get('config_comment_status')) {
			$json['error']['warning'] = $this->language->get('error_status');
		}

		$this->load->model('cms/article');

		$article_info = $this->model_cms_article->getArticle($article_id);

		if (!$article_info) {
			$json['error']['warning'] = $this->language->get('error_article');
		}

		if (!$this->customer->isLogged() && !$this->config->get('config_comment_guest')) {
			$json['error']['warning'] = $this->language->get('error_guest');
		}

		if ((oc_strlen($this->request->post['author']) < 3) || (oc_strlen($this->request->post['author']) > 25)) {
			$json['error']['author'] = $this->language->get('error_author');
		}

		if ((oc_strlen($this->request->post['comment']) < 2) || (oc_strlen($this->request->post['comment']) > 1000)) {
			$json['error']['comment'] = $this->language->get('error_comment');
		}

		// Captcha
		$this->load->model('setting/extension');

		$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

		if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('comment', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code'] . '.validate');

			if ($captcha) {
				$json['error']['captcha'] = $captcha;
			}
		}

		if (!$json) {
			$comment_approve = (int)$this->config->get('config_comment_approve');

			// Anti-Spam
			$this->load->model('cms/antispam');

			$spam = $this->model_cms_antispam->getSpam($this->request->post['comment']);

			if (!$this->customer->isCommenter() && $spam) {
				$status = 0;
			} else {
				$status = 1;
			}

			$comment_data = $this->request->post + [
					'parent_id' => $parent_id,
					'ip'        => $this->request->server['REMOTE_ADDR'],
					'status'    => $status
				];

			$this->model_cms_article->addComment($article_id, $comment_data);

			if (!$status) {
				$json['success'] = $this->language->get('text_queue');
			} else {
				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Rating
	 *
	 * @return void
	 */
	public function rating(): void {
		$this->load->language('cms/comment');

		$json = [];

		if (isset($this->request->get['article_id'])) {
			$article_id = (int)$this->request->get['article_id'];
		} else {
			$article_id = 0;
		}

		if (isset($this->request->get['article_comment_id'])) {
			$article_comment_id = (int)$this->request->get['article_comment_id'];
		} else {
			$article_comment_id = 0;
		}

		$this->load->model('cms/article');

		$article_info = $this->model_cms_article->getArticle($article_id);

		if (!$article_info) {
			$json['error']['warning'] = $this->language->get('error_article');
		}

		// Comment
		$article_info = $this->model_cms_article->getComment($article_id);

		if (!$article_info) {
			$json['error']['warning'] = $this->language->get('error_article');
		}

		if (!isset($this->request->get['comment_token']) || !isset($this->session->data['comment_token']) || $this->request->get['comment_token'] != $this->session->data['comment_token']) {
			$json['error']['warning'] = $this->language->get('error_token');
		}

		if (!$this->customer->isLogged() && !$this->config->get('config_comment_guest')) {
			$json['error']['warning'] = $this->language->get('error_guest');
		}

		if (!$json) {
			// Anti-Spam
			$rating_data = $this->request->post + [
					'rating' => (bool)$this->request->get['rating'],
					'ip'     => $this->request->server['REMOTE_ADDR']
				];

			$this->load->model('cms/antispam');

			$this->model_cms_article->addRating($article_id, $rating_data);

			$json['success'] = $this->language->get('text_success_rating');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
