<?php
/**
 * AI読書推薦システム
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/openai_client.php');
require_once(dirname(__FILE__) . '/author_corrections.php');

class AIBookRecommender {
    private ?OpenAIClient $client;
    
    public function __construct() {
        $this->client = getOpenAIClient();
    }
    
    /**
     * ユーザーの読書履歴に基づいて本を推薦
     * 
     * @param array $readingHistory 読書履歴（本のタイトル、著者、評価）
     * @param array $preferences ユーザーの好み（ジャンル、テーマなど）
     * @param int $count 推薦する本の数
     * @return array 推薦結果
     */
    public function recommendBooks(array $readingHistory, array $preferences = [], int $count = 5): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }
        
        try {
            // 読書履歴の著者名を修正
            $readingHistory = AuthorCorrections::correctReadingHistory($readingHistory);
            
            $systemPrompt = $this->createRecommendationSystemPrompt();
            $userPrompt = $this->createRecommendationUserPrompt($readingHistory, $preferences, $count);
            
            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.8,
                1500
            );
            
            $recommendationText = OpenAIClient::extractText($response);
            $recommendations = $this->parseRecommendations($recommendationText);
            
            // デバッグ用
            error_log("AI Recommendation Text: " . $recommendationText);
            error_log("Parsed Recommendations: " . json_encode($recommendations));
            
            return [
                'success' => true,
                'recommendations' => $recommendations,
                'raw_text' => $recommendationText, // デバッグ用
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 読書傾向を分析
     * 
     * @param array $readingHistory 読書履歴
     * @return array 分析結果
     */
    public function analyzeReadingTrends(array $readingHistory): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }
        
        if (empty($readingHistory)) {
            return [
                'success' => false,
                'error' => '読書履歴がありません'
            ];
        }
        
        try {
            // 読書履歴の著者名を修正
            $readingHistory = AuthorCorrections::correctReadingHistory($readingHistory);
            
            $systemPrompt = "あなたは読書傾向の分析専門家です。ユーザーの読書履歴から、具体的な作品名や著者名を挙げながら、詳細な読書傾向を分析してください。";
            
            $historyText = $this->formatReadingHistory($readingHistory);
            
            $userPrompt = "以下の読書履歴を詳細に分析してください。\n\n{$historyText}\n\n";
            $userPrompt .= "次の形式で、具体的な本のタイトルや著者名を含めながら分析してください：\n\n";
            $userPrompt .= "【読書タイプ】\n";
            $userPrompt .= "この読者のタイプを、読んだ本の具体例を挙げながら説明\n\n";
            $userPrompt .= "【ジャンル傾向と代表作】\n";
            $userPrompt .= "よく読むジャンルと、そのジャンルで読んだ具体的な作品を紹介\n\n";
            $userPrompt .= "【著者の好み】\n";
            $userPrompt .= "繰り返し読んでいる著者や、好んでいる著者の特徴を具体名を挙げて説明\n\n";
            $userPrompt .= "【読書パターンの分析】\n";
            $userPrompt .= "◆ 高評価（★4以上）の作品の共通点を、具体的な作品名を挙げながら分析\n";
            $userPrompt .= "◆ 最近読んだ本から見える読書傾向の変化や成長\n";
            $userPrompt .= "◆ 特定の時期に集中して読んでいるテーマやジャンル\n\n";
            $userPrompt .= "【読書の特徴】\n";
            $userPrompt .= "・「〇〇」や「△△」のような[ジャンル]を好む傾向\n";
            $userPrompt .= "・[著者名]の作品を複数読むなど、気に入った著者を深く追求\n";
            $userPrompt .= "・評価の分布から見える読書の選び方の特徴\n\n";
            $userPrompt .= "【今後の読書への提案】\n";
            $userPrompt .= "現在の読書傾向を踏まえて、次に読むと良い方向性を提案\n";
            
            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.5,
                1500  // 分析結果が途中で途切れないように増加
            );
            
            if (!$response || !is_array($response)) {
                error_log('AI Book Recommender: Invalid response from OpenAI');
                return [
                    'success' => false,
                    'error' => 'AIからの応答が不正です'
                ];
            }
            
            $analysis = OpenAIClient::extractText($response);
            
            if (empty($analysis)) {
                error_log('AI Book Recommender: Empty analysis from OpenAI');
                return [
                    'success' => false,
                    'error' => '分析結果が空です'
                ];
            }
            
            // 分析結果内の著者名も修正
            $analysis = AuthorCorrections::correctInText($analysis);
            
            return [
                'success' => true,
                'analysis' => $analysis,
                'book_count' => count($readingHistory),
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 次に読むべき本を提案（読書チャレンジ）
     * 
     * @param array $readingHistory 読書履歴
     * @param string $challenge チャレンジの種類（新ジャンル、古典、など）
     * @return array 提案結果
     */
    public function suggestReadingChallenge(array $readingHistory, string $challenge = '新しいジャンル'): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }
        
        try {
            // 読書履歴の著者名を修正
            $readingHistory = AuthorCorrections::correctReadingHistory($readingHistory);
            
            $systemPrompt = "あなたは読書アドバイザーです。ユーザーの読書の幅を広げるため、新しいチャレンジを提案してください。";
            
            $historyText = $this->formatReadingHistory($readingHistory);
            
            $userPrompt = "読書履歴:\n{$historyText}\n\n";
            $userPrompt .= "チャレンジ: {$challenge}\n\n";
            $userPrompt .= "この読者に合った、でも今までとは違う本を3冊提案してください。\n\n";
            $userPrompt .= "以下の形式で回答してください：\n\n";
            $userPrompt .= "1. [タイトル] / [著者]\n";
            $userPrompt .= "   チャレンジ理由: [なぜこの本がチャレンジになるか]\n";
            $userPrompt .= "   新しい視点: [読むことで得られる新しい視点]\n";
            $userPrompt .= "   ジャンル: [ジャンル]\n\n";
            
            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',  // GPT-5 miniモデルを使用
                0.9,
                1500  // チャレンジ提案も十分な長さを確保
            );
            
            $challengeText = OpenAIClient::extractText($response);
            
            // チャレンジ提案をパースして構造化データも返す
            $parsedChallenges = $this->parseChallengeRecommendations($challengeText);
            
            return [
                'success' => true,
                'challenge' => $challenge,
                'suggestions' => $challengeText,
                'parsed_suggestions' => $parsedChallenges,
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 推薦システムプロンプトを作成
     */
    private function createRecommendationSystemPrompt(): string {
        return "あなたは経験豊富な書店員で、読者の好みを理解し、最適な本を推薦する専門家です。
読者の読書履歴と好みに基づいて、次に読むべき本を提案してください。

推薦の原則:
1. 読者の好みのパターンを理解する
2. 評価の高かった本と似た要素を持つ本を含める
3. 少し新しい要素も取り入れて読書の幅を広げる
4. 日本で入手可能な本を優先する
5. 各推薦には明確な理由を付ける";
    }
    
    /**
     * 推薦用ユーザープロンプトを作成
     */
    private function createRecommendationUserPrompt(array $readingHistory, array $preferences, int $count): string {
        $prompt = "以下の読書履歴を持つ読者に、{$count}冊の本を推薦してください。\n\n";
        
        // 読書履歴
        $prompt .= "読書履歴:\n";
        $prompt .= $this->formatReadingHistory($readingHistory) . "\n\n";
        
        // 好み
        if (!empty($preferences)) {
            $prompt .= "読者の好み:\n";
            foreach ($preferences as $key => $value) {
                if (is_array($value)) {
                    // 配列の場合は、要素を結合
                    $prompt .= "- {$key}: " . implode(', ', array_filter($value)) . "\n";
                } else {
                    $prompt .= "- {$key}: {$value}\n";
                }
            }
            $prompt .= "\n";
        }
        
        $prompt .= "以下の形式で推薦してください:\n\n";
        $prompt .= "1. **「[タイトル]」 / [著者]**\n";
        $prompt .= "   推薦理由: [なぜこの本がおすすめか]\n";
        $prompt .= "   ジャンル: [ジャンル]\n\n";
        $prompt .= "注意: タイトルは必ず「」で囲んでください。";
        
        return $prompt;
    }

    /**
     * 月間レポートの要約を生成
     *
     * @param int $year 年
     * @param int $month 月
     * @param array $reportData レポートデータ（statistics, books）
     * @return array 生成結果
     */
    public function generateMonthlySummary(int $year, int $month, array $reportData): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }

        $stats = $reportData['stats'] ?? [];
        $books = $reportData['books'] ?? [];

        if (empty($books) && ($stats['books_finished'] ?? 0) == 0) {
            return [
                'success' => false,
                'error' => 'この月の読書データがありません'
            ];
        }

        try {
            $systemPrompt = "あなたは優しく温かみのある読書アドバイザーです。ユーザーの月間読書レポートとレビューを見て、その人の読書体験に寄り添った要約コメントを生成してください。ユーザーのレビューから読み取れる感想や気づきを反映させてください。日本語で350-450文字程度で書いてください。";

            // 読了本リストをフォーマット（レビュー付き）
            $booksText = "";
            foreach (array_slice($books, 0, 10) as $i => $book) {
                $title = mb_substr($book['title'] ?? '', 0, 40);
                $author = mb_substr($book['author'] ?? '', 0, 20);
                $rating = isset($book['rating']) && $book['rating'] > 0 ? "★{$book['rating']}" : '';
                $review = isset($book['review']) && !empty($book['review']) ? mb_substr($book['review'], 0, 150) : '';

                $booksText .= ($i + 1) . ". 「{$title}」{$author} {$rating}\n";
                if (!empty($review)) {
                    $booksText .= "   レビュー: {$review}\n";
                }
            }
            if (count($books) > 10) {
                $booksText .= "...他" . (count($books) - 10) . "冊\n";
            }

            $userPrompt = "{$year}年{$month}月の読書レポート:\n\n";
            $userPrompt .= "【統計】\n";
            $userPrompt .= "- 読了冊数: " . ($stats['books_finished'] ?? count($books)) . "冊\n";
            $userPrompt .= "- 読んだページ: " . number_format($stats['pages_read'] ?? 0) . "ページ\n";
            $userPrompt .= "- 日平均: " . ($stats['daily_average'] ?? 0) . "ページ\n";

            if (($stats['goal'] ?? 0) > 0) {
                $goalStatus = ($stats['goal_achieved'] ?? false) ? '達成！' : '未達成';
                $userPrompt .= "- 目標: {$stats['goal']}冊 → {$goalStatus}（" . ($stats['goal_progress'] ?? 0) . "%）\n";
            }

            $userPrompt .= "\n【読了した本とレビュー】\n{$booksText}\n";
            $userPrompt .= "この月の読書を振り返る温かいコメントを書いてください。ユーザーのレビューから読み取れる感想や発見に触れながら、具体的な本のタイトルを挙げて、読者の頑張りを褒め、次の月への励ましも添えてください。";

            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.7,
                600
            );

            if (!$response || !is_array($response)) {
                return [
                    'success' => false,
                    'error' => 'AIからの応答が不正です'
                ];
            }

            $summary = OpenAIClient::extractText($response);

            if (empty($summary)) {
                return [
                    'success' => false,
                    'error' => '要約の生成に失敗しました'
                ];
            }

            // 著者名を修正
            $summary = AuthorCorrections::correctInText($summary);

            return [
                'success' => true,
                'summary' => $summary,
                'year' => $year,
                'month' => $month,
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];

        } catch (Exception $e) {
            error_log('Monthly Summary Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => '要約生成中にエラーが発生しました'
            ];
        }
    }

    /**
     * 年間読書レポートの要約を生成
     *
     * @param int $year 年
     * @param array $reportData レポートデータ（stats, monthly_data, books）
     * @return array 生成結果
     */
    public function generateYearlySummary(int $year, array $reportData): array {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OpenAI client is not available'
            ];
        }

        $stats = $reportData['stats'] ?? [];
        $books = $reportData['books'] ?? [];
        $monthlyData = $reportData['monthly_data'] ?? [];

        if (empty($books) && ($stats['books_finished'] ?? 0) == 0) {
            return [
                'success' => false,
                'error' => 'この年の読書データがありません'
            ];
        }

        try {
            $systemPrompt = "あなたは優しく温かみのある読書アドバイザーです。ユーザーの年間読書レポートとレビューを見て、1年間の読書体験を振り返る温かいコメントを生成してください。ユーザーのレビューから読み取れる感想や成長を反映させてください。日本語で450-550文字程度で書いてください。";

            // 月別推移をフォーマット
            $monthlyText = "";
            $bestMonth = 0;
            $bestMonthCount = 0;
            foreach ($monthlyData as $m) {
                $month = $m['month'] ?? 0;
                $count = $m['books'] ?? 0;
                if ($count > $bestMonthCount) {
                    $bestMonth = $month;
                    $bestMonthCount = $count;
                }
                if ($count > 0) {
                    $monthlyText .= "{$month}月: {$count}冊 ";
                }
            }

            // 読了本リストをフォーマット（レビュー付き）
            $booksText = "";
            foreach (array_slice($books, 0, 15) as $i => $book) {
                $title = mb_substr($book['title'] ?? '', 0, 40);
                $author = mb_substr($book['author'] ?? '', 0, 20);
                $rating = isset($book['rating']) && $book['rating'] > 0 ? "★{$book['rating']}" : '';
                $month = $book['month'] ?? '';
                $review = isset($book['review']) && !empty($book['review']) ? mb_substr($book['review'], 0, 100) : '';

                $booksText .= ($i + 1) . ". 「{$title}」{$author} {$rating}";
                if ($month) {
                    $booksText .= " ({$month}月)";
                }
                $booksText .= "\n";
                if (!empty($review)) {
                    $booksText .= "   レビュー: {$review}\n";
                }
            }
            if (count($books) > 15) {
                $booksText .= "...他" . (count($books) - 15) . "冊\n";
            }

            $userPrompt = "{$year}年の年間読書レポート:\n\n";
            $userPrompt .= "【年間統計】\n";
            $userPrompt .= "- 年間読了冊数: " . ($stats['books_finished'] ?? count($books)) . "冊\n";
            $userPrompt .= "- 読んだページ: " . number_format($stats['pages_read'] ?? 0) . "ページ\n";
            $userPrompt .= "- 読書日数: " . ($stats['reading_days'] ?? 0) . "日\n";
            $userPrompt .= "- 月平均: " . ($stats['monthly_average'] ?? 0) . "冊\n";

            if ($bestMonth > 0) {
                $userPrompt .= "- ベスト月: {$bestMonth}月 ({$bestMonthCount}冊)\n";
            }

            if (($stats['longest_streak'] ?? 0) > 0) {
                $userPrompt .= "- 最長連続読書: " . $stats['longest_streak'] . "日間\n";
            }

            if (!empty($monthlyText)) {
                $userPrompt .= "\n【月別推移】\n{$monthlyText}\n";
            }

            $userPrompt .= "\n【読了した本とレビュー】\n{$booksText}\n";
            $userPrompt .= "この1年間の読書を振り返る温かいコメントを書いてください。";
            $userPrompt .= "ユーザーのレビューから読み取れる感想や発見に触れながら、印象的な本のタイトルを挙げて、1年間の読書の軌跡を称え、来年への期待や励ましも添えてください。";

            $response = $this->client->chatWithSystem(
                $systemPrompt,
                $userPrompt,
                'gpt-4o-mini',
                0.7,
                800
            );

            if (!$response || !is_array($response)) {
                return [
                    'success' => false,
                    'error' => 'AIからの応答が不正です'
                ];
            }

            $summary = OpenAIClient::extractText($response);

            if (empty($summary)) {
                return [
                    'success' => false,
                    'error' => '要約の生成に失敗しました'
                ];
            }

            // 著者名を修正
            $summary = AuthorCorrections::correctInText($summary);

            return [
                'success' => true,
                'summary' => $summary,
                'year' => $year,
                'tokens_used' => OpenAIClient::getUsedTokens($response)
            ];

        } catch (Exception $e) {
            error_log('Yearly Summary Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => '要約生成中にエラーが発生しました'
            ];
        }
    }

    /**
     * 読書履歴をフォーマット
     */
    private function formatReadingHistory(array $readingHistory): string {
        $formatted = "";
        $count = 0;
        $maxBooks = 25; // 分析用には25冊に制限
        
        // 評価の高い順、最近の順でソート
        usort($readingHistory, function($a, $b) {
            // まず評価でソート
            $ratingDiff = ($b['rating'] ?? 0) - ($a['rating'] ?? 0);
            if ($ratingDiff !== 0) {
                return $ratingDiff;
            }
            // 評価が同じ場合は最近の順
            return 0;
        });
        
        foreach ($readingHistory as $book) {
            if ($count >= $maxBooks) break;
            
            $formatted .= sprintf(
                "%d. 「%s」%s",
                $count + 1,
                mb_substr($book['title'], 0, 40),
                !empty($book['author']) ? " / " . mb_substr($book['author'], 0, 20) : ""
            );
            
            if (isset($book['rating']) && $book['rating'] > 0) {
                $formatted .= " [評価: " . $book['rating'] . "/5]";
            }
            
            $formatted .= "\n";
            $count++;
        }
        
        if (count($readingHistory) > $maxBooks) {
            $formatted .= "\n...および他" . (count($readingHistory) - $maxBooks) . "冊\n";
        }
        
        return $formatted;
    }
    
    /**
     * 推薦結果をパース
     */
    private function parseRecommendations(string $text): array {
        $recommendations = [];
        
        // 簡単なパーサー（実際にはもっと洗練されたパースが必要）
        $lines = explode("\n", $text);
        $currentRec = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // タイトル行を検出 (1. **「タイトル」 / 著者**)
            if (preg_match('/^\d+\.\s*\*\*「(.+?)」\s*\/\s*(.+?)\*\*$/', $line, $matches)) {
                if ($currentRec) {
                    $recommendations[] = $currentRec;
                }
                $currentRec = [
                    'title' => $matches[1],
                    'author' => $matches[2],
                    'reason' => '',
                    'genre' => ''
                ];
            } elseif ($currentRec) {
                if (strpos($line, '推薦理由:') === 0) {
                    $currentRec['reason'] = trim(substr($line, strlen('推薦理由:')));
                } elseif (strpos($line, 'ジャンル:') === 0) {
                    $currentRec['genre'] = trim(substr($line, strlen('ジャンル:')));
                }
            }
        }
        
        if ($currentRec) {
            $recommendations[] = $currentRec;
        }
        
        return $recommendations;
    }
    
    /**
     * チャレンジ提案結果をパース
     */
    private function parseChallengeRecommendations(string $text): array {
        $recommendations = [];
        
        // 簡単なパーサー
        $lines = explode("\n", $text);
        $currentRec = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // タイトル行を検出 - 複数のフォーマットに対応
            // 1. [タイトル] / [著者] (プロンプトで指定した形式)
            // 2. **「タイトル」 / 著者** (実際に返ってくることがある形式)
            // 3. 「タイトル」 / 著者 (シンプルな形式)
            $titleMatch = false;
            $title = '';
            $author = '';
            
            if (preg_match('/^\d+\.\s*\[(.+?)\]\s*\/\s*\[(.+?)\]/', $line, $matches)) {
                $titleMatch = true;
                $title = $matches[1];
                $author = $matches[2];
            } elseif (preg_match('/^\d+\.\s*\*\*「(.+?)」\s*\/\s*(.+?)\*\*$/', $line, $matches)) {
                $titleMatch = true;
                $title = $matches[1];
                $author = $matches[2];
            } elseif (preg_match('/^\d+\.\s*「(.+?)」\s*\/\s*(.+?)$/', $line, $matches)) {
                $titleMatch = true;
                $title = $matches[1];
                $author = $matches[2];
            }
            
            if ($titleMatch) {
                if ($currentRec) {
                    $recommendations[] = $currentRec;
                }
                $currentRec = [
                    'title' => $title,
                    'author' => $author,
                    'challenge_reason' => '',
                    'new_perspective' => '',
                    'genre' => ''
                ];
            } elseif ($currentRec) {
                if (strpos($line, 'チャレンジ理由:') === 0) {
                    $currentRec['challenge_reason'] = trim(substr($line, strlen('チャレンジ理由:')));
                } elseif (strpos($line, '新しい視点:') === 0) {
                    $currentRec['new_perspective'] = trim(substr($line, strlen('新しい視点:')));
                } elseif (strpos($line, 'ジャンル:') === 0) {
                    $currentRec['genre'] = trim(substr($line, strlen('ジャンル:')));
                }
            }
        }
        
        if ($currentRec) {
            $recommendations[] = $currentRec;
        }
        
        return $recommendations;
    }
}