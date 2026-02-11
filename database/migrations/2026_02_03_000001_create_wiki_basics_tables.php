<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wiki Talent Basics
        Schema::create('wiki_talent_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Wiki ID');
            $table->string('name', 255)->comment('タレント名');
            $table->string('normalized_name', 255)->comment('正規化タレント名');
            $table->string('real_name', 255)->default('')->comment('本名');
            $table->string('normalized_real_name', 255)->default('')->comment('正規化本名');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->uuid('agency_identifier')->nullable()->comment('所属事務所ID');
            $table->string('emoji', 16)->default('')->comment('絵文字');
            $table->string('representative_symbol', 32)->default('')->comment('代表シンボル');
            $table->string('position', 64)->default('')->comment('ポジション');
            $table->string('mbti', 8)->nullable()->comment('MBTI');
            $table->string('zodiac_sign', 32)->nullable()->comment('星座');
            $table->string('english_level', 32)->nullable()->comment('英語レベル');
            $table->integer('height')->nullable()->comment('身長(cm)');
            $table->string('blood_type', 8)->nullable()->comment('血液型');
            $table->string('fandom_name', 64)->default('')->comment('ファンダム名');
            $table->uuid('profile_image_identifier')->nullable()->comment('プロフィール画像識別子');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('wikis')
                ->onDelete('cascade');

            $table->index('normalized_name');
        });

        // Wiki Talent Basic Groups (pivot)
        Schema::create('wiki_talent_basic_groups', static function (Blueprint $table) {
            $table->uuid('wiki_id')->comment('Wiki ID');
            $table->uuid('group_identifier')->comment('グループWiki ID');
            $table->primary(['wiki_id', 'group_identifier']);

            $table->foreign('wiki_id')
                ->references('wiki_id')
                ->on('wiki_talent_basics')
                ->onDelete('cascade');
        });

        // Wiki Group Basics
        Schema::create('wiki_group_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Wiki ID');
            $table->string('name', 255)->comment('グループ名');
            $table->string('normalized_name', 255)->comment('正規化グループ名');
            $table->uuid('agency_identifier')->nullable()->comment('所属事務所ID');
            $table->string('group_type', 32)->nullable()->comment('グループタイプ');
            $table->string('status', 32)->nullable()->comment('ステータス');
            $table->string('generation', 16)->nullable()->comment('世代');
            $table->date('debut_date')->nullable()->comment('デビュー日');
            $table->date('disband_date')->nullable()->comment('解散日');
            $table->string('fandom_name', 64)->default('')->comment('ファンダム名');
            $table->jsonb('official_colors')->default('[]')->comment('公式カラー配列');
            $table->string('emoji', 16)->default('')->comment('絵文字');
            $table->string('representative_symbol', 32)->default('')->comment('代表シンボル');
            $table->uuid('main_image_identifier')->nullable()->comment('メイン画像識別子');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('wikis')
                ->onDelete('cascade');

            $table->index('normalized_name');
        });

        // Wiki Agency Basics
        Schema::create('wiki_agency_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Wiki ID');
            $table->string('name', 255)->comment('事務所名');
            $table->string('normalized_name', 255)->comment('正規化事務所名');
            $table->string('ceo', 255)->default('')->comment('CEO');
            $table->string('normalized_ceo', 255)->default('')->comment('正規化CEO');
            $table->date('founded_in')->nullable()->comment('設立日');
            $table->uuid('parent_agency_identifier')->nullable()->comment('親事務所ID');
            $table->string('status', 32)->nullable()->comment('ステータス');
            $table->uuid('logo_image_identifier')->nullable()->comment('ロゴ画像識別子');
            $table->string('official_website', 512)->nullable()->comment('公式ウェブサイト');
            $table->jsonb('social_links')->default('[]')->comment('SNSリンク配列');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('wikis')
                ->onDelete('cascade');

            $table->index('normalized_name');
        });

        // Wiki Song Basics
        Schema::create('wiki_song_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Wiki ID');
            $table->string('name', 255)->comment('曲名');
            $table->string('normalized_name', 255)->comment('正規化曲名');
            $table->string('song_type', 32)->nullable()->comment('曲タイプ');
            $table->jsonb('genres')->default('[]')->comment('ジャンル配列');
            $table->uuid('agency_identifier')->nullable()->comment('事務所ID');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->string('album_name', 255)->nullable()->comment('アルバム名');
            $table->uuid('cover_image_identifier')->nullable()->comment('カバー画像識別子');
            $table->string('lyricist', 255)->default('')->comment('作詞家');
            $table->string('normalized_lyricist', 255)->default('')->comment('正規化作詞家');
            $table->string('composer', 255)->default('')->comment('作曲家');
            $table->string('normalized_composer', 255)->default('')->comment('正規化作曲家');
            $table->string('arranger', 255)->default('')->comment('編曲家');
            $table->string('normalized_arranger', 255)->default('')->comment('正規化編曲家');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('wikis')
                ->onDelete('cascade');

            $table->index('normalized_name');
        });

        // Wiki Song Basic Groups (pivot)
        Schema::create('wiki_song_basic_groups', static function (Blueprint $table) {
            $table->uuid('wiki_id')->comment('Wiki ID');
            $table->uuid('group_identifier')->comment('グループWiki ID');
            $table->primary(['wiki_id', 'group_identifier']);

            $table->foreign('wiki_id')
                ->references('wiki_id')
                ->on('wiki_song_basics')
                ->onDelete('cascade');
        });

        // Wiki Song Basic Talents (pivot)
        Schema::create('wiki_song_basic_talents', static function (Blueprint $table) {
            $table->uuid('wiki_id')->comment('Wiki ID');
            $table->uuid('talent_identifier')->comment('タレントWiki ID');
            $table->primary(['wiki_id', 'talent_identifier']);

            $table->foreign('wiki_id')
                ->references('wiki_id')
                ->on('wiki_song_basics')
                ->onDelete('cascade');
        });

        // Draft Wiki Basics
        Schema::create('draft_wiki_talent_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Draft Wiki ID');
            $table->string('name', 255)->comment('タレント名');
            $table->string('normalized_name', 255)->comment('正規化タレント名');
            $table->string('real_name', 255)->default('')->comment('本名');
            $table->string('normalized_real_name', 255)->default('')->comment('正規化本名');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->uuid('agency_identifier')->nullable()->comment('所属事務所ID');
            $table->string('emoji', 16)->default('')->comment('絵文字');
            $table->string('representative_symbol', 32)->default('')->comment('代表シンボル');
            $table->string('position', 64)->default('')->comment('ポジション');
            $table->string('mbti', 8)->nullable()->comment('MBTI');
            $table->string('zodiac_sign', 32)->nullable()->comment('星座');
            $table->string('english_level', 32)->nullable()->comment('英語レベル');
            $table->integer('height')->nullable()->comment('身長(cm)');
            $table->string('blood_type', 8)->nullable()->comment('血液型');
            $table->string('fandom_name', 64)->default('')->comment('ファンダム名');
            $table->uuid('profile_image_identifier')->nullable()->comment('プロフィール画像識別子');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('draft_wikis')
                ->onDelete('cascade');
        });

        // Draft Wiki Talent Basic Groups (pivot)
        Schema::create('draft_wiki_talent_basic_groups', static function (Blueprint $table) {
            $table->uuid('wiki_id')->comment('Draft Wiki ID');
            $table->uuid('group_identifier')->comment('グループWiki ID');
            $table->primary(['wiki_id', 'group_identifier']);

            $table->foreign('wiki_id')
                ->references('wiki_id')
                ->on('draft_wiki_talent_basics')
                ->onDelete('cascade');
        });

        Schema::create('draft_wiki_group_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Draft Wiki ID');
            $table->string('name', 255)->comment('グループ名');
            $table->string('normalized_name', 255)->comment('正規化グループ名');
            $table->uuid('agency_identifier')->nullable()->comment('所属事務所ID');
            $table->string('group_type', 32)->nullable()->comment('グループタイプ');
            $table->string('status', 32)->nullable()->comment('ステータス');
            $table->string('generation', 16)->nullable()->comment('世代');
            $table->date('debut_date')->nullable()->comment('デビュー日');
            $table->date('disband_date')->nullable()->comment('解散日');
            $table->string('fandom_name', 64)->default('')->comment('ファンダム名');
            $table->jsonb('official_colors')->default('[]')->comment('公式カラー配列');
            $table->string('emoji', 16)->default('')->comment('絵文字');
            $table->string('representative_symbol', 32)->default('')->comment('代表シンボル');
            $table->uuid('main_image_identifier')->nullable()->comment('メイン画像識別子');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('draft_wikis')
                ->onDelete('cascade');
        });

        Schema::create('draft_wiki_agency_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Draft Wiki ID');
            $table->string('name', 255)->comment('事務所名');
            $table->string('normalized_name', 255)->comment('正規化事務所名');
            $table->string('ceo', 255)->default('')->comment('CEO');
            $table->string('normalized_ceo', 255)->default('')->comment('正規化CEO');
            $table->date('founded_in')->nullable()->comment('設立日');
            $table->uuid('parent_agency_identifier')->nullable()->comment('親事務所ID');
            $table->string('status', 32)->nullable()->comment('ステータス');
            $table->uuid('logo_image_identifier')->nullable()->comment('ロゴ画像識別子');
            $table->string('official_website', 512)->nullable()->comment('公式ウェブサイト');
            $table->jsonb('social_links')->default('[]')->comment('SNSリンク配列');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('draft_wikis')
                ->onDelete('cascade');
        });

        Schema::create('draft_wiki_song_basics', static function (Blueprint $table) {
            $table->uuid('wiki_id')->primary()->comment('Draft Wiki ID');
            $table->string('name', 255)->comment('曲名');
            $table->string('normalized_name', 255)->comment('正規化曲名');
            $table->string('song_type', 32)->nullable()->comment('曲タイプ');
            $table->jsonb('genres')->default('[]')->comment('ジャンル配列');
            $table->uuid('agency_identifier')->nullable()->comment('事務所ID');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->string('album_name', 255)->nullable()->comment('アルバム名');
            $table->uuid('cover_image_identifier')->nullable()->comment('カバー画像識別子');
            $table->string('lyricist', 255)->default('')->comment('作詞家');
            $table->string('normalized_lyricist', 255)->default('')->comment('正規化作詞家');
            $table->string('composer', 255)->default('')->comment('作曲家');
            $table->string('normalized_composer', 255)->default('')->comment('正規化作曲家');
            $table->string('arranger', 255)->default('')->comment('編曲家');
            $table->string('normalized_arranger', 255)->default('')->comment('正規化編曲家');
            $table->timestamps();

            $table->foreign('wiki_id')
                ->references('id')
                ->on('draft_wikis')
                ->onDelete('cascade');
        });

        // Draft Wiki Song Basic Groups (pivot)
        Schema::create('draft_wiki_song_basic_groups', static function (Blueprint $table) {
            $table->uuid('wiki_id')->comment('Draft Wiki ID');
            $table->uuid('group_identifier')->comment('グループWiki ID');
            $table->primary(['wiki_id', 'group_identifier']);

            $table->foreign('wiki_id')
                ->references('wiki_id')
                ->on('draft_wiki_song_basics')
                ->onDelete('cascade');
        });

        // Draft Wiki Song Basic Talents (pivot)
        Schema::create('draft_wiki_song_basic_talents', static function (Blueprint $table) {
            $table->uuid('wiki_id')->comment('Draft Wiki ID');
            $table->uuid('talent_identifier')->comment('タレントWiki ID');
            $table->primary(['wiki_id', 'talent_identifier']);

            $table->foreign('wiki_id')
                ->references('wiki_id')
                ->on('draft_wiki_song_basics')
                ->onDelete('cascade');
        });

        // Wiki Snapshot Basics
        Schema::create('wiki_snapshot_talent_basics', static function (Blueprint $table) {
            $table->uuid('snapshot_id')->primary()->comment('Snapshot ID');
            $table->string('name', 255)->comment('タレント名');
            $table->string('normalized_name', 255)->comment('正規化タレント名');
            $table->string('real_name', 255)->default('')->comment('本名');
            $table->string('normalized_real_name', 255)->default('')->comment('正規化本名');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->uuid('agency_identifier')->nullable()->comment('所属事務所ID');
            $table->string('emoji', 16)->default('')->comment('絵文字');
            $table->string('representative_symbol', 32)->default('')->comment('代表シンボル');
            $table->string('position', 64)->default('')->comment('ポジション');
            $table->string('mbti', 8)->nullable()->comment('MBTI');
            $table->string('zodiac_sign', 32)->nullable()->comment('星座');
            $table->string('english_level', 32)->nullable()->comment('英語レベル');
            $table->integer('height')->nullable()->comment('身長(cm)');
            $table->string('blood_type', 8)->nullable()->comment('血液型');
            $table->string('fandom_name', 64)->default('')->comment('ファンダム名');
            $table->uuid('profile_image_identifier')->nullable()->comment('プロフィール画像識別子');
            $table->timestamps();

            $table->foreign('snapshot_id')
                ->references('id')
                ->on('wiki_snapshots')
                ->onDelete('cascade');
        });

        // Wiki Snapshot Talent Basic Groups (pivot)
        Schema::create('wiki_snapshot_talent_basic_groups', static function (Blueprint $table) {
            $table->uuid('snapshot_id')->comment('Snapshot ID');
            $table->uuid('group_identifier')->comment('グループWiki ID');
            $table->primary(['snapshot_id', 'group_identifier']);

            $table->foreign('snapshot_id')
                ->references('snapshot_id')
                ->on('wiki_snapshot_talent_basics')
                ->onDelete('cascade');
        });

        Schema::create('wiki_snapshot_group_basics', static function (Blueprint $table) {
            $table->uuid('snapshot_id')->primary()->comment('Snapshot ID');
            $table->string('name', 255)->comment('グループ名');
            $table->string('normalized_name', 255)->comment('正規化グループ名');
            $table->uuid('agency_identifier')->nullable()->comment('所属事務所ID');
            $table->string('group_type', 32)->nullable()->comment('グループタイプ');
            $table->string('status', 32)->nullable()->comment('ステータス');
            $table->string('generation', 16)->nullable()->comment('世代');
            $table->date('debut_date')->nullable()->comment('デビュー日');
            $table->date('disband_date')->nullable()->comment('解散日');
            $table->string('fandom_name', 64)->default('')->comment('ファンダム名');
            $table->jsonb('official_colors')->default('[]')->comment('公式カラー配列');
            $table->string('emoji', 16)->default('')->comment('絵文字');
            $table->string('representative_symbol', 32)->default('')->comment('代表シンボル');
            $table->uuid('main_image_identifier')->nullable()->comment('メイン画像識別子');
            $table->timestamps();

            $table->foreign('snapshot_id')
                ->references('id')
                ->on('wiki_snapshots')
                ->onDelete('cascade');
        });

        Schema::create('wiki_snapshot_agency_basics', static function (Blueprint $table) {
            $table->uuid('snapshot_id')->primary()->comment('Snapshot ID');
            $table->string('name', 255)->comment('事務所名');
            $table->string('normalized_name', 255)->comment('正規化事務所名');
            $table->string('ceo', 255)->default('')->comment('CEO');
            $table->string('normalized_ceo', 255)->default('')->comment('正規化CEO');
            $table->date('founded_in')->nullable()->comment('設立日');
            $table->uuid('parent_agency_identifier')->nullable()->comment('親事務所ID');
            $table->string('status', 32)->nullable()->comment('ステータス');
            $table->uuid('logo_image_identifier')->nullable()->comment('ロゴ画像識別子');
            $table->string('official_website', 512)->nullable()->comment('公式ウェブサイト');
            $table->jsonb('social_links')->default('[]')->comment('SNSリンク配列');
            $table->timestamps();

            $table->foreign('snapshot_id')
                ->references('id')
                ->on('wiki_snapshots')
                ->onDelete('cascade');
        });

        Schema::create('wiki_snapshot_song_basics', static function (Blueprint $table) {
            $table->uuid('snapshot_id')->primary()->comment('Snapshot ID');
            $table->string('name', 255)->comment('曲名');
            $table->string('normalized_name', 255)->comment('正規化曲名');
            $table->string('song_type', 32)->nullable()->comment('曲タイプ');
            $table->jsonb('genres')->default('[]')->comment('ジャンル配列');
            $table->uuid('agency_identifier')->nullable()->comment('事務所ID');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->string('album_name', 255)->nullable()->comment('アルバム名');
            $table->uuid('cover_image_identifier')->nullable()->comment('カバー画像識別子');
            $table->string('lyricist', 255)->default('')->comment('作詞家');
            $table->string('normalized_lyricist', 255)->default('')->comment('正規化作詞家');
            $table->string('composer', 255)->default('')->comment('作曲家');
            $table->string('normalized_composer', 255)->default('')->comment('正規化作曲家');
            $table->string('arranger', 255)->default('')->comment('編曲家');
            $table->string('normalized_arranger', 255)->default('')->comment('正規化編曲家');
            $table->timestamps();

            $table->foreign('snapshot_id')
                ->references('id')
                ->on('wiki_snapshots')
                ->onDelete('cascade');
        });

        // Wiki Snapshot Song Basic Groups (pivot)
        Schema::create('wiki_snapshot_song_basic_groups', static function (Blueprint $table) {
            $table->uuid('snapshot_id')->comment('Snapshot ID');
            $table->uuid('group_identifier')->comment('グループWiki ID');
            $table->primary(['snapshot_id', 'group_identifier']);

            $table->foreign('snapshot_id')
                ->references('snapshot_id')
                ->on('wiki_snapshot_song_basics')
                ->onDelete('cascade');
        });

        // Wiki Snapshot Song Basic Talents (pivot)
        Schema::create('wiki_snapshot_song_basic_talents', static function (Blueprint $table) {
            $table->uuid('snapshot_id')->comment('Snapshot ID');
            $table->uuid('talent_identifier')->comment('タレントWiki ID');
            $table->primary(['snapshot_id', 'talent_identifier']);

            $table->foreign('snapshot_id')
                ->references('snapshot_id')
                ->on('wiki_snapshot_song_basics')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Snapshot pivot tables
        Schema::dropIfExists('wiki_snapshot_song_basic_talents');
        Schema::dropIfExists('wiki_snapshot_song_basic_groups');
        Schema::dropIfExists('wiki_snapshot_talent_basic_groups');

        // Snapshot basics
        Schema::dropIfExists('wiki_snapshot_song_basics');
        Schema::dropIfExists('wiki_snapshot_agency_basics');
        Schema::dropIfExists('wiki_snapshot_group_basics');
        Schema::dropIfExists('wiki_snapshot_talent_basics');

        // Draft pivot tables
        Schema::dropIfExists('draft_wiki_song_basic_talents');
        Schema::dropIfExists('draft_wiki_song_basic_groups');
        Schema::dropIfExists('draft_wiki_talent_basic_groups');

        // Draft basics
        Schema::dropIfExists('draft_wiki_song_basics');
        Schema::dropIfExists('draft_wiki_agency_basics');
        Schema::dropIfExists('draft_wiki_group_basics');
        Schema::dropIfExists('draft_wiki_talent_basics');

        // Wiki pivot tables
        Schema::dropIfExists('wiki_song_basic_talents');
        Schema::dropIfExists('wiki_song_basic_groups');
        Schema::dropIfExists('wiki_talent_basic_groups');

        // Wiki basics
        Schema::dropIfExists('wiki_song_basics');
        Schema::dropIfExists('wiki_agency_basics');
        Schema::dropIfExists('wiki_group_basics');
        Schema::dropIfExists('wiki_talent_basics');
    }
};
