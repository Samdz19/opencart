<?php
namespace Opencart\Catalog\Model\Cms;
/**
 * Class Article
 *
 * @package Opencart\Catalog\Model\Cms
 */
class Article extends \Opencart\System\Engine\Model {
	/**
	 * @param int $article_id
	 *
	 * @return array
	 */
	public function getArticle(int $article_id): array {
		$sql = "SELECT DISTINCT * FROM `" . DB_PREFIX . "article` `a` LEFT JOIN `" . DB_PREFIX . "article_description` `ad` ON (`a`.`article_id` = `ad`.`article_id`) LEFT JOIN `" . DB_PREFIX . "article_to_store` `a2s` ON (`a`.`article_id` = `a2s`.`article_id`) WHERE `a`.`article_id` = '" . (int)$article_id . "' AND `ad`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `a2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `a`.`status` = '1'";

		$article_data = $this->cache->get('article.' . md5($sql));

		if (!$article_data) {
			$query = $this->db->query($sql);

			$article_data = $query->row;

			$this->cache->set('article.' . md5($sql), $article_data);
		}

		return $article_data;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function getArticles(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "article` `a` LEFT JOIN `" . DB_PREFIX . "article_description` `ad` ON (`a`.`article_id` = `ad`.`article_id`) LEFT JOIN `" . DB_PREFIX . "article_to_store` `a2s` ON (`a`.`article_id` = `a2s`.`article_id`) WHERE `ad`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `a2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `a`.`status` = '1'";

		if (!empty($data['filter_search'])) {
			$sql .= " AND (";

			$implode = [];

			$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_search'])));

			foreach ($words as $word) {
				$implode[] = "`ad`.`name` LIKE '" . $this->db->escape('%' . $word . '%') . "'";
			}

			if ($implode) {
				$sql .= " (" . implode(" OR ", $implode) . ")";
			}

			$sql .= " OR `ad`.`description` LIKE '" . $this->db->escape('%' . (string)$data['filter_search'] . '%') . "'";

			$implode = [];

			foreach ($words as $word) {
				$implode[] = "`ad`.`tag` LIKE '" . $this->db->escape('%' . $word . '%') . "'";
			}

			if ($implode) {
				$sql .= " OR (" . implode(" OR ", $implode) . ")";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_topic_id'])) {
			$sql .= " AND `a`.`topic_id` = '" . (int)$data['filter_topic_id'] . "'";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND `a`.`author` = '" . $this->db->escape($data['filter_author']) . "'";
		}

		$sql .= " ORDER BY `a`.`date_added` DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$key = md5($sql);

		$article_data = $this->cache->get('article.' . $key);

		if (!$article_data) {
			$query = $this->db->query($sql);

			$article_data = $query->rows;

			$this->cache->set('article.' . $key, $article_data);
		}

		return $article_data;
	}

	/**
	 * @param array $data
	 *
	 * @return int
	 */
	public function getTotalArticles(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "article` `a` LEFT JOIN `" . DB_PREFIX . "article_description` `ad` ON (`a`.`article_id` = `ad`.`article_id`) LEFT JOIN `" . DB_PREFIX . "article_to_store` `a2s` ON (`a`.`article_id` = `a2s`.`article_id`) WHERE `ad`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `a2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_search'])) {
			$sql .= " AND (";

			$implode = [];

			$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_search'])));

			foreach ($words as $word) {
				$implode[] = "`ad`.`name` LIKE '" . $this->db->escape('%' . $word . '%') . "'";
			}

			if ($implode) {
				$sql .= " (" . implode(" OR ", $implode) . ")";
			}

			$sql .= " OR `ad`.`description` LIKE '" . $this->db->escape('%' . (string)$data['filter_search'] . '%') . "'";

			$implode = [];

			foreach ($words as $word) {
				$implode[] = "`ad`.`tag` LIKE '" . $this->db->escape('%' . $word . '%') . "'";
			}

			if ($implode) {
				$sql .= " OR (" . implode(" OR ", $implode) . ")";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_topic_id'])) {
			$sql .= " AND `a`.`topic_id` = '" . (int)$data['filter_topic_id'] . "'";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND `a`.`author` = '" . $this->db->escape($data['filter_author']) . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	/**
	 * @param int $article_id
	 *
	 * @return array
	 */
	public function getLayoutId(int $article_id): int {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "article_to_layout` WHERE `article_id` = '" . (int)$article_id . "' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	/**
	 * addComment
	 *
	 * @param int   $article_id
	 * @param array $data
	 *
	 * @return int
	 */
	public function addComment(int $article_id, array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "article_comment` SET `article_id` = '" . (int)$article_id . "', `parent_id` = '" . (int)$data['parent_id'] . "', `customer_id` = '" . (int)$this->customer->getId() . "', `author` = '" . $this->db->escape((string)$data['author']) . "', `comment` = '" . $this->db->escape((string)$data['comment']) . "', `ip` = '" . $this->db->escape((string)$data['ip']) . "', `status` = '" . (bool)!empty($data['status']) . "', `date_added` = NOW()");

		$this->cache->delete('comment');

		return $this->db->getLastId();
	}

	/**
	 * getComments
	 *
	 * @param int   $article_id
	 * @param array $data
	 *
	 * @return array
	 */

	public function getComments(int $article_id, array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "article_comment` WHERE `article_id` = '" . (int)$article_id . "'";

		if (isset($data['parent_id'])) {
			$sql .= " AND `parent_id` = '" . (int)$data['parent_id'] . "'";
		}

		$sql .= " AND `status` = '1'";

		$sort_data = [
			'rating',
			'date_added'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `date_added`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$key = md5($sql);

		$comment_data = $this->cache->get('comment.' . $key);

		if (!$comment_data) {
			$query = $this->db->query($sql);

			$comment_data = $query->rows;

			$this->cache->set('comment.' . $key, $comment_data);

		}

		return $comment_data;
	}

	/**
	 * @param int $article_id
	 *
	 * @return int
	 */
	public function getTotalComments(int $article_id, array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "article_comment` WHERE `article_id` = '" . (int)$article_id . "'";

		if (isset($data['parent_id'])) {
			$sql .= " AND `parent_id` = '" . (int)$data['parent_id'] . "'";
		}

		$sql .= " AND `status` = '1'";

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	/**
	 * Add Rating
	 *
	 * @param int   $article_id
	 * @param array $data
	 */
	public function addRating(int $article_id, array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "article_comment` SET `article_id` = '" . (int)$article_id . "', `article_comment_id` = '" . (int)$data['article_comment_id'] . "', `customer_id` = '" . (int)$this->customer->getId() . "', `comment` = '" . $this->db->escape((string)$data['comment']) . "', `ip` = '" . $this->db->escape((string)$data['ip']) . "', `status` = '" . (bool)!empty($data['status']) . "', `date_added` = NOW()");
	}
}
