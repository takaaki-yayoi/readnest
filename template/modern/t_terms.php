<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- ヘッダーセクション -->
    <div class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-6">
                <i class="fas fa-shield-alt text-6xl opacity-80"></i>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">利用規約・プライバシーポリシー</h1>
            <p class="text-xl text-white opacity-90">
                ReadNestをご利用いただく前にお読みください
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                    <span class="text-gray-700 dark:text-gray-300">利用規約・プライバシーポリシー</span>
                </li>
            </ol>
        </nav>

        <!-- ナビゲーション -->
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">目次</h2>
            <ul class="space-y-2">
                <li>
                    <a href="#terms" class="text-readnest-primary hover:text-readnest-accent dark:text-readnest-primary dark:hover:text-readnest-accent">
                        <i class="fas fa-file-contract mr-2"></i>利用規約
                    </a>
                </li>
                <li>
                    <a href="#privacy" class="text-readnest-primary hover:text-readnest-accent dark:text-readnest-primary dark:hover:text-readnest-accent">
                        <i class="fas fa-user-shield mr-2"></i>プライバシーポリシー
                    </a>
                </li>
            </ul>
        </div>

        <!-- 利用規約 -->
        <section id="terms" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                <i class="fas fa-file-contract text-readnest-primary mr-3"></i>
                利用規約
            </h2>
            
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-700 dark:text-gray-300 mb-6">
                    この利用規約（以下「本規約」といいます）は、ReadNest（以下「当サービス」といいます）の利用条件を定めるものです。
                    ユーザーの皆さま（以下「ユーザー」といいます）には、本規約に従って当サービスをご利用いただきます。
                </p>

                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第1条（適用）</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            本規約は、ユーザーと当サービスの利用に関わる一切の関係に適用されるものとします。
                        </p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第2条（利用登録）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>登録希望者が当サービスの定める方法によって利用登録を申請し、当サービスがこれを承認することによって、利用登録が完了するものとします。</li>
                            <li>当サービスは、以下の場合には、利用登録の申請を承認しないことがあります。
                                <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                    <li>虚偽の事項を届け出た場合</li>
                                    <li>本規約に違反したことがある者からの申請である場合</li>
                                    <li>その他、当サービスが利用登録を相当でないと判断した場合</li>
                                </ul>
                            </li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第3条（ユーザーIDおよびパスワードの管理）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>ユーザーは、自己の責任において、当サービスのユーザーIDおよびパスワードを適切に管理するものとします。</li>
                            <li>ユーザーは、いかなる場合にも、ユーザーIDおよびパスワードを第三者に譲渡または貸与することはできません。</li>
                            <li>当サービスは、ユーザーIDとパスワードの組み合わせが登録情報と一致してログインされた場合には、そのユーザーIDを登録しているユーザー自身による利用とみなします。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第4条（禁止事項）</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-3">ユーザーは、当サービスの利用にあたり、以下の行為をしてはなりません。</p>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>法令または公序良俗に違反する行為</li>
                            <li>犯罪行為に関連する行為</li>
                            <li>当サービスのサーバーまたはネットワークの機能を破壊したり、妨害したりする行為</li>
                            <li>当サービスの運営を妨害するおそれのある行為</li>
                            <li>他のユーザーに関する個人情報等を収集または蓄積する行為</li>
                            <li>他のユーザーに成りすます行為</li>
                            <li>当サービスに関連して、反社会的勢力に対して直接または間接に利益を供与する行為</li>
                            <li>その他、当サービスが不適切と判断する行為</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第5条（本サービスの提供の停止等）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスは、以下のいずれかの事由があると判断した場合、ユーザーに事前に通知することなく本サービスの全部または一部の提供を停止または中断することができるものとします。
                                <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                    <li>本サービスにかかるコンピュータシステムの保守点検または更新を行う場合</li>
                                    <li>地震、落雷、火災、停電または天災などの不可抗力により、本サービスの提供が困難となった場合</li>
                                    <li>コンピュータまたは通信回線等が事故により停止した場合</li>
                                    <li>その他、当サービスが本サービスの提供が困難と判断した場合</li>
                                </ul>
                            </li>
                            <li>当サービスは、本サービスの提供の停止または中断により、ユーザーまたは第三者が被ったいかなる不利益または損害についても、一切の責任を負わないものとします。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第6条（著作権）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>ユーザーは、自ら著作権等の必要な知的財産権を有するか、または必要な権利者の許諾を得た文章、画像や映像等の情報のみ、当サービスを利用し、投稿または編集することができるものとします。</li>
                            <li>ユーザーが当サービスを利用して投稿または編集した文章、画像、映像等の著作権については、当該ユーザーその他既存の権利者に留保されるものとします。</li>
                            <li>前項にかかわらず、当サービスは、当サービスを利用して投稿または編集された文章、画像、映像等を利用できるものとし、ユーザーは、この利用に関して、著作者人格権を行使しないものとします。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第7条（利用制限および登録抹消）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスは、以下の場合には、事前の通知なく、ユーザーに対して、本サービスの全部もしくは一部の利用を制限し、またはユーザーとしての登録を抹消することができるものとします。
                                <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                    <li>本規約のいずれかの条項に違反した場合</li>
                                    <li>登録事項に虚偽の事実があることが判明した場合</li>
                                    <li>その他、当サービスが本サービスの利用を適当でないと判断した場合</li>
                                </ul>
                            </li>
                            <li>当サービスは、本条に基づき当サービスが行った行為によりユーザーに生じた損害について、一切の責任を負いません。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第8条（免責事項）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスの債務不履行責任は、当サービスの故意または重過失によらない場合には免責されるものとします。</li>
                            <li>当サービスは、本サービスに関して、ユーザーと他のユーザーまたは第三者との間において生じた取引、連絡または紛争等について一切責任を負いません。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第9条（サービス内容の変更等）</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            当サービスは、ユーザーに通知することなく、本サービスの内容を変更しまたは本サービスの提供を中止することができるものとし、これによってユーザーに生じた損害について一切の責任を負いません。
                        </p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第10条（利用規約の変更）</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            当サービスは、必要と判断した場合には、ユーザーに通知することなくいつでも本規約を変更することができるものとします。なお、本規約の変更後、本サービスの利用を開始した場合には、当該ユーザーは変更後の規約に同意したものとみなします。
                        </p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第11条（準拠法・裁判管轄）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>本規約の解釈にあたっては、日本法を準拠法とします。</li>
                            <li>本サービスに関して紛争が生じた場合には、当サービスの本店所在地を管轄する裁判所を専属的合意管轄とします。</li>
                        </ol>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-700 rounded">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        制定日：2024年1月1日<br>
                        最終更新日：2024年1月1日
                    </p>
                </div>
            </div>
        </section>

        <!-- プライバシーポリシー -->
        <section id="privacy" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                <i class="fas fa-user-shield text-readnest-primary mr-3"></i>
                プライバシーポリシー
            </h2>

            <div class="prose prose-lg max-w-none">
                <p class="text-gray-700 dark:text-gray-300 mb-6">
                    ReadNest（以下「当サービス」といいます）は、ユーザーの個人情報の取扱いについて、以下のとおりプライバシーポリシー（以下「本ポリシー」といいます）を定めます。
                </p>

                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第1条（個人情報）</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            「個人情報」とは、個人情報保護法にいう「個人情報」を指すものとし、生存する個人に関する情報であって、当該情報に含まれる氏名、メールアドレス、その他の記述等により特定の個人を識別できる情報を指します。
                        </p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第2条（個人情報の収集方法）</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            当サービスは、ユーザーが利用登録をする際に、メールアドレス、ニックネームなどの個人情報をお尋ねすることがあります。また、ユーザーと提携先などとの間でなされたユーザーの個人情報を含む取引記録や決済に関する情報を、当サービスの提携先（情報提供元、広告主、広告配信先などを含みます。以下「提携先」といいます）などから収集することがあります。
                        </p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第3条（個人情報を収集・利用する目的）</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-3">当サービスが個人情報を収集・利用する目的は、以下のとおりです。</p>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスの提供・運営のため</li>
                            <li>ユーザーからのお問い合わせに回答するため（本人確認を行うことを含む）</li>
                            <li>ユーザーが利用中のサービスの新機能、更新情報、キャンペーン等の案内のメールを送付するため</li>
                            <li>メンテナンス、重要なお知らせなど必要に応じたご連絡のため</li>
                            <li>利用規約に違反したユーザーや、不正・不当な目的でサービスを利用しようとするユーザーの特定をし、ご利用をお断りするため</li>
                            <li>ユーザーにご自身の登録情報の閲覧や変更、削除、ご利用状況の閲覧を行っていただくため</li>
                            <li>上記の利用目的に付随する目的</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第4条（利用目的の変更）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスは、利用目的が変更前と関連性を有すると合理的に認められる場合に限り、個人情報の利用目的を変更するものとします。</li>
                            <li>利用目的の変更を行った場合には、変更後の目的について、当サービス所定の方法により、ユーザーに通知し、または本ウェブサイト上に公表するものとします。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第5条（個人情報の第三者提供）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスは、次に掲げる場合を除いて、あらかじめユーザーの同意を得ることなく、第三者に個人情報を提供することはありません。ただし、個人情報保護法その他の法令で認められる場合を除きます。
                                <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                    <li>人の生命、身体または財産の保護のために必要がある場合であって、本人の同意を得ることが困難であるとき</li>
                                    <li>公衆衛生の向上または児童の健全な育成の推進のために特に必要がある場合であって、本人の同意を得ることが困難であるとき</li>
                                    <li>国の機関もしくは地方公共団体またはその委託を受けた者が法令の定める事務を遂行することに対して協力する必要がある場合であって、本人の同意を得ることにより当該事務の遂行に支障を及ぼすおそれがあるとき</li>
                                    <li>予め次の事項を告知あるいは公表し、かつ当サービスが個人情報保護委員会に届出をしたとき
                                        <ul class="list-circle list-inside ml-6 mt-2 space-y-1">
                                            <li>利用目的に第三者への提供を含むこと</li>
                                            <li>第三者に提供されるデータの項目</li>
                                            <li>第三者への提供の手段または方法</li>
                                            <li>本人の求めに応じて個人情報の第三者への提供を停止すること</li>
                                            <li>本人の求めを受け付ける方法</li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li>前項の定めにかかわらず、次に掲げる場合には、当該情報の提供先は第三者に該当しないものとします。
                                <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                    <li>当サービスが利用目的の達成に必要な範囲内において個人情報の取扱いの全部または一部を委託する場合</li>
                                    <li>合併その他の事由による事業の承継に伴って個人情報が提供される場合</li>
                                    <li>個人情報を特定の者との間で共同して利用する場合であって、その旨並びに共同して利用される個人情報の項目、共同して利用する者の範囲、利用する者の利用目的および当該個人情報の管理について責任を有する者の氏名または名称について、あらかじめ本人に通知し、または本人が容易に知り得る状態に置いた場合</li>
                                </ul>
                            </li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第6条（個人情報の開示）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスは、本人から個人情報の開示を求められたときは、本人に対し、遅滞なくこれを開示します。ただし、開示することにより次のいずれかに該当する場合は、その全部または一部を開示しないこともあり、開示しない決定をした場合には、その旨を遅滞なく通知します。
                                <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                    <li>本人または第三者の生命、身体、財産その他の権利利益を害するおそれがある場合</li>
                                    <li>当サービスの業務の適正な実施に著しい支障を及ぼすおそれがある場合</li>
                                    <li>その他法令に違反することとなる場合</li>
                                </ul>
                            </li>
                            <li>前項の定めにかかわらず、履歴情報および特性情報などの個人情報以外の情報については、原則として開示いたしません。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第7条（個人情報の訂正および削除）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>ユーザーは、当サービスの保有する自己の個人情報が誤った情報である場合には、当サービスが定める手続きにより、当サービスに対して個人情報の訂正、追加または削除（以下「訂正等」といいます）を請求することができます。</li>
                            <li>当サービスは、ユーザーから前項の請求を受けてその請求に応じる必要があると判断した場合には、遅滞なく、当該個人情報の訂正等を行うものとします。</li>
                            <li>当サービスは、前項の規定に基づき訂正等を行った場合、または訂正等を行わない旨の決定をしたときは遅滞なく、これをユーザーに通知します。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第8条（個人情報の利用停止等）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>当サービスは、本人から、個人情報が、利用目的の範囲を超えて取り扱われているという理由、または不正の手段により取得されたものであるという理由により、その利用の停止または消去（以下「利用停止等」といいます）を求められた場合には、遅滞なく必要な調査を行います。</li>
                            <li>前項の調査結果に基づき、その請求に応じる必要があると判断した場合には、遅滞なく、当該個人情報の利用停止等を行います。</li>
                            <li>当サービスは、前項の規定に基づき利用停止等を行った場合、または利用停止等を行わない旨の決定をしたときは、遅滞なく、これをユーザーに通知します。</li>
                            <li>前2項にかかわらず、利用停止等に多額の費用を有する場合その他利用停止等を行うことが困難な場合であって、ユーザーの権利利益を保護するために必要なこれに代わるべき措置をとれる場合は、この代替策を講じるものとします。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第9条（Cookie（クッキー）の使用について）</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-3">
                            当サービスでは、ユーザーの利便性向上のため、Cookie（クッキー）を使用しています。
                        </p>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>Cookieとは、ウェブサイトを訪問したユーザーの情報を一時的に保存する仕組みです。</li>
                            <li>当サービスでは、以下の目的でCookieを使用しています：
                                <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                    <li>ログイン状態の維持</li>
                                    <li>ユーザーの設定情報の保存</li>
                                    <li>サービスの利用状況の分析</li>
                                </ul>
                            </li>
                            <li>ユーザーは、ブラウザの設定によりCookieの受け取りを拒否することができます。ただし、Cookieを無効にした場合、当サービスの一部の機能が利用できなくなる可能性があります。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第10条（プライバシーポリシーの変更）</h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li>本ポリシーの内容は、法令その他本ポリシーに別段の定めのある事項を除いて、ユーザーに通知することなく、変更することができるものとします。</li>
                            <li>当サービスが別途定める場合を除いて、変更後のプライバシーポリシーは、本ウェブサイトに掲載したときから効力を生じるものとします。</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">第11条（お問い合わせ窓口）</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            本ポリシーに関するお問い合わせは、下記の窓口までお願いいたします。
                        </p>
                        <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded">
                            <p class="text-gray-700 dark:text-gray-300">
                                サービス名：ReadNest<br>
                                Eメールアドレス：admin@readnest.jp
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-700 rounded">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        制定日：2024年1月1日<br>
                        最終更新日：2024年1月1日
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- スムーススクロール -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // スムーススクロール
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>