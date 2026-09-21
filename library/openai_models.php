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
 *  - max_tokens は 400エラー → max_completion_tokens に変換（出力量が多いため3倍に換算）
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

    // 呼び出し側の max_tokens は gpt-4o-mini の出力量を基準に決められている。
    // gpt-5.6-luna は同じプロンプトで約2.5〜3.5倍のトークンを出力するため（実測: 226 → 572〜780）、
    // そのままだとJSONが途中で切れてパースに失敗する。上限なので引き上げても実出力分しか課金されない。
    if (isset($params['max_tokens'])) {
        $params['max_completion_tokens'] = $params['max_tokens'] * 3;
        unset($params['max_tokens']);
    }

    if (!isset($params['verbosity'])) {
        // low: 出力量と応答時間を従来モデルに近づける（medium比で約2割減）
        $params['verbosity'] = defined('OPENAI_VERBOSITY') ? OPENAI_VERBOSITY : 'low';
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
