<?php
/**
 * Copyright 2020 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace DummyApp;

use DummyApp\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Supplier extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * @return HasOneThrough
     */
    public function userHistory(): HasOneThrough
    {
        return $this->hasOneThrough(History::class, User::class);
    }

    protected static function newFactory()
    {
        return SupplierFactory::new();
    }
}
