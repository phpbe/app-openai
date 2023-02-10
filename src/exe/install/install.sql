
CREATE TABLE `openai_completion_session` (
  `id` varchar(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'uuid()' COMMENT 'UUID',
  `name` varchar(600) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `lines` tinyint(4) NOT NULL DEFAULT '1' COMMENT '行数',
  `is_complete` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否完成',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分类';

ALTER TABLE `openai_completion_session`
ADD PRIMARY KEY (`id`);


CREATE TABLE `openai_completion_session_message` (
  `id` varchar(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'uuid()' COMMENT 'UUID',
  `completion_session_id` varchar(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '会话ID',
  `line` tinyint(4) NOT NULL DEFAULT '1' COMMENT '行号',
  `question` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '提问',
  `answer` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '回签',
  `times` tinyint(4) NOT NULL DEFAULT '0' COMMENT '失败重试次数',
  `is_complete` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否完成',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分类';

ALTER TABLE `openai_completion_session_message`
ADD PRIMARY KEY (`id`),
ADD KEY `completion_session_id` (`completion_session_id`);
