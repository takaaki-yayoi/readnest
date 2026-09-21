<?php
/**
 * OpenAI チャットモデル設定の一元管理
 *
 * モデル名は各ファイルに直書きせず、必ず openaiChatModel() を使うこと。
 * config.php で OPENAI_MODEL / OPENAI_REASONING_EFFORT を定義すれば上書きできる。
 */

/**
 * チャット用モデル名を取得
 */
function openaiChatModel(): string {
    return defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-5.6-luna';
}

/**
 * Chat Completions のリクエストパラメータを、使用モデルに合わせて変換する
 *
 * 呼び出し側は従来どおり max_tokens / temperature / presence_penalty 等を指定してよい。
 * gpt-5系（推論モデル）では以下の差異があるためここで吸収する（2026-09 実APIで確認）:
 *  - max_tokens は 400エラー → max_completion_tokens に変換
 *  - temperature は reasoning_effort=none のときのみ指定可（それ以外は400エラー）
 *  - presence_penalty / frequency_penalty を送るとエラーにならずタイムアウトまでハングする
 * gpt-4系 / gpt-3.5系に戻した場合はパラメータをそのまま送る。
 *
 * @param array $params model 未指定なら openaiChatModel() を補う
 * @return array
 */
function openaiChatParams(array $params): array {
    if (empty($params['model'])) {
        $params['model'] = openaiChatModel();
    }

    // 旧世代モデルは従来パラメータをそのまま受け付ける
    if (preg_match('/^gpt-(3\.5|4)/', $params['model']) === 1) {
        return $params;
    }

    if (isset($params['max_tokens'])) {
        $params['max_completion_tokens'] = $params['max_tokens'];
        unset($params['max_tokens']);
    }

    if (!isset($params['reasoning_effort'])) {
        // none: 推論トークンを使わない（従来モデルと同等の速度・コスト、temperature指定可）
        $params['reasoning_effort'] = defined('OPENAI_REASONING_EFFORT') ? OPENAI_REASONING_EFFORT : 'none';
    }

    if ($params['reasoning_effort'] !== 'none') {
        unset($params['temperature']);
    }

    unset($params['presence_penalty'], $params['frequency_penalty']);

    return $params;
}
