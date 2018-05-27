<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */


use Illuminate\Database\Seeder;
use App\Content\Section;
use App\Content\Category;
use App\Content\MultiLangContent;
use App\User;
use App\Role;
use Carbon\Carbon;

/**
 * Remove all the existing actions and roles and re(store) the actions based in the config file
 * Then calls the UsersAndRolesSeeder to refresh the roles actions
 */
class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $firstAdmin = User::whereHas('roles', function ($query) {
            $query->where('slug', Role::defaultAdminRoleSlug());
        })->orderBy("id", "asc")->first();

        if (!Section::where("url", "/")->exists()) {
            $multiLangContent = MultiLangContent::create(['type'=>"section", 'owner_id'=>$firstAdmin->id]);
            $locales = Config::get('i18n.locales');

            foreach ($locales as $key => $value) {
                $content = $key === "pt-BR"? "Bem vindo": "Welcome";
                $section = Section::create([
                    'title'=>"Home",
                    'url'=>"/",
                    'locale'=>$key,
                    'multi_lang_content_id'=>$multiLangContent->id,
                    'has_single'=>true,
                    'content'=>$content
                ]);
                $section->users()->attach($firstAdmin->id);
            }
        }

        if (!Category::where("slug", "travel")->exists()) {
            $multiLangContent = MultiLangContent::create(['type'=>"category", 'owner_id'=>$firstAdmin->id]);
            $locales = Config::get('i18n.locales');

            foreach ($locales as $key => $value) {
                $label = $key === "pt-BR"? "Viagem": "Travel";
                $slug = $key === "pt-BR"? "viagem": "travel";
                Category::create([
                    'label'=>$label,
                    'slug'=>$slug,
                    'locale'=>$key,
                    'multi_lang_content_id'=>$multiLangContent->id,
                    'parent_category_id'=>null,
                ]);
            }

        }

    }
}
