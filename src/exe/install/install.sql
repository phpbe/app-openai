
CREATE TABLE `openai_chat_completion` (
  `id` varchar(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'uuid()' COMMENT 'UUID',
  `prompt` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '提问',
  `lines` tinyint(4) NOT NULL DEFAULT '1' COMMENT '行数',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文本应该会话';

ALTER TABLE `openai_chat_completion`
ADD PRIMARY KEY (`id`);


CREATE TABLE `openai_chat_completion_message` (
  `id` varchar(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'uuid()' COMMENT 'UUID',
  `chat_completion_id` varchar(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '会话ID',
  `line` tinyint(4) NOT NULL DEFAULT '1' COMMENT '行号',
  `prompt` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '提问',
  `answer` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '回答',
  `times` tinyint(4) NOT NULL DEFAULT '0' COMMENT '失败重试次数',
  `is_complete` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否完成',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文本应该会话记录';

ALTER TABLE `openai_chat_completion_message`
ADD PRIMARY KEY (`id`),
ADD KEY `chat_completion_id` (`chat_completion_id`);




CREATE TABLE `openai_image_generation` (
  `id` varchar(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'uuid()' COMMENT 'UUID',
  `prompt` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '提问',
  `options` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '提问',
  `url` varchar(600) COLLATE utf8mb4_general_ci NOT NULL COMMENT '参数',
  `local_url` varchar(300) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '保存到本地的网址',
  `times` tinyint(4) NOT NULL DEFAULT '0' COMMENT '失败重试次数',
  `is_complete` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否完成',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='图像生成';


ALTER TABLE `openai_image_generation`
ADD PRIMARY KEY (`id`);

