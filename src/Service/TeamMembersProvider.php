<?php

namespace App\Service;

final class TeamMembersProvider
{
    /**
     * @return list<array{name: string, position: string, initials: string, bio: string, image: string}>
     */
    public function all(): array
    {
        return [
            [
                'name' => 'Yaninna Grace Alanunay',
                'position' => 'Founder & Creative Director',
                'initials' => 'YA',
                'bio' => 'Keeps the vision for Cloudrobe sharp and makes sure every color story still feels like cosplay, not costume.',
                'image' => 'images/collections/liz-b-naril.jpg',
            ],
            [
                'name' => 'Yaninna Grace',
                'position' => 'Creative Lead',
                'initials' => 'YG',
                'bio' => 'Translates reference photos into builds that move well, read on camera, and survive the convention floor.',
                'image' => 'images/collections/wonyoung-g-wapa.jpg',
            ],
            [
                'name' => 'Yannie Alanunay',
                'position' => 'Design & Styling',
                'initials' => 'YN',
                'bio' => 'Obsessed with silhouettes, wig lines, and the tiny tweaks that make a character instantly recognizable.',
                'image' => 'images/collections/rei.jpg',
            ],
            [
                'name' => 'Yan Alanunay',
                'position' => 'Operations & Support',
                'initials' => 'YA',
                'bio' => 'Keeps orders, shipping, and customer messages flowing so builds arrive where they need to, when they need to.',
                'image' => 'images/collections/gaeul.jpg',
            ],
        ];
    }
}
