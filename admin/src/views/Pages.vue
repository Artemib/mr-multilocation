<template>
  <div>
    <div v-if="bulkConfirmOpen" class="fixed top-12 left-0 right-0 bg-yellow-100 text-yellow-800 p-3 z-50 border-b border-yellow-300 flex items-center justify-between shadow-md">
      <span>Применить настройки к <strong>{{ bulk.selectedPageIds.length }}</strong> выбранным страницам?</span>
      <div class="flex gap-2">
        <Button variant="primary" size="sm" @click="confirmBulk">Да, применить</Button>
        <Button variant="secondary" size="sm" @click="bulkConfirmOpen = false">Отмена</Button>
      </div>
    </div>
    <h3 class="text-lg font-medium mb-2">Страницы (CPT: multiregional_page)</h3>
    <div class="flex items-center gap-2 mb-4">
      <Button variant="primary" @click="openCreateModal">Добавить</Button>
      <a :href="newUrl" class="text-slate-600 underline" target="_blank">Добавить в WP</a>
      <a :href="listUrl" class="text-slate-600 underline" target="_blank">Открыть список в WP</a>
    </div>

    <!-- Поиск и фильтры -->
    <div class="mb-4 flex items-end gap-2">
      <div class="flex-1">
        <Input 
          v-model="tableSearchQuery" 
          placeholder="Поиск..."
          class="w-full"
        />
      </div>
      <div class="flex gap-2">
        <Button variant="secondary" @click="openBulkModal">Массовые настройки</Button>
        <Button @click="filtersModalOpen = true">
          Фильтры
          <span v-if="tableFilterFolders.length > 0 || tableFilterSubdomains.length > 0" class="ml-1 text-xs">
            ({{ tableFilterFolders.length + tableFilterSubdomains.length }})
          </span>
        </Button>
      </div>
    </div>
    <div class="mb-4 text-xs text-slate-500">
      Найдено: {{ tableFilteredItems.length }} из {{ items.length }}
    </div>

    <!-- Попап фильтров -->
    <Modal 
      v-model="filtersModalOpen"
      title="Фильтры"
      size="lg"
    >
      <template #header-actions>
        <Button variant="ghost" size="sm" @click="resetFilters">Сбросить фильтры</Button>
      </template>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600 font-medium">Папки</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllTableFolders"
                class="text-xs"
              >
                {{ allTableFoldersSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="tableFolderSearch" 
              placeholder="Поиск папок..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredTableFolders.length }} из {{ allFolders.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="f in filteredTableFolders" :key="f.id" class="block mb-1">
                <input 
                  type="checkbox" 
                  :value="Number(f.id)" 
                  v-model="tableFilterFolders" 
                  @change="handleFolderCheckboxChange"
                /> <span v-html="highlightText(f.slug, tableFolderSearch)"></span>
              </label>
              <div v-if="filteredTableFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600 font-medium">Поддомены</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllTableSubdomains"
                class="text-xs"
              >
                {{ allTableSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="tableSubdomainSearch" 
              placeholder="Поиск поддоменов..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredTableSubdomains.length }} из {{ allSubdomains.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="s in filteredTableSubdomains" :key="s.id" class="block mb-1">
                <input 
                  type="checkbox" 
                  :value="Number(s.id)" 
                  v-model="tableFilterSubdomains" 
                  @change="handleSubdomainCheckboxChange"
                /> <span v-html="highlightText(s.slug, tableSubdomainSearch)"></span>
              </label>
              <div v-if="filteredTableSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
        </div>
        <div class="flex gap-2 justify-end">
          <Button variant="secondary" @click="closeFiltersModal">Закрыть</Button>
        </div>
    </Modal>

    <div v-if="error" class="text-red-700 mb-2">{{ error }}</div>
    <div class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="py-2 pr-4 w-8">
              <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" />
            </th>
            <th class="py-2 pr-4">Название</th>
            <th class="py-2 pr-4">Папки</th>
            <th class="py-2 pr-4">Поддомены</th>
            <th class="py-2 pr-4">URL</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in displayedItems" :key="p.id" class="border-b align-top">
            <td class="py-2 pr-4">
              <input type="checkbox" :value="p.id" v-model="selectedIds" />
            </td>
            <td class="py-2 pr-4">
              <div class="relative">
                <button 
                  @click.stop="showEditMenu(p, $event)"
                  :data-edit-menu-button="p.id"
                  :data-page-id="p.id"
                  class="underline hover:text-blue-600 cursor-pointer"
                  v-html="highlightText(p.title, tableSearchQuery)"
                ></button>
                <div 
                  v-if="editMenuId === p.id" 
                  :data-edit-menu-id="p.id"
                  class="absolute z-50 mt-1 bg-white border rounded shadow-lg p-2 min-w-[200px]"
                  @click.stop
                >
                  <button 
                    @click="editInWp(p)"
                    class="block w-full text-left px-3 py-2 hover:bg-slate-100 rounded text-sm"
                  >
                    📝 Редактировать в WordPress
                  </button>
                  <button 
                    @click="editInOurForm(p)"
                    class="block w-full text-left px-3 py-2 hover:bg-slate-100 rounded text-sm"
                  >
                    ✏️ Редактировать в нашей форме
                  </button>
                  <button 
                    @click="editMenuId = null; document.removeEventListener('click', closeEditMenuOnOutsideClick, true);"
                    class="block w-full text-left px-3 py-2 hover:bg-slate-100 rounded text-sm text-slate-500"
                  >
                    Отмена
                  </button>
                </div>
              </div>
              <div class="text-slate-500" v-html="highlightText(p.slug, tableSearchQuery)"></div>
            </td>
            <td class="py-2 pr-4">
              <div v-if="p.visibility?.folders?.length" v-html="highlightText(mapFolderIdsToSlugs(p.visibility.folders, p.folders).join(', '), tableSearchQuery)"></div>
              <div v-else class="text-slate-500">—</div>
            </td>
            <td class="py-2 pr-4">
              <div v-if="p.visibility?.subdomains?.length" v-html="highlightText(mapSubIdsToSlugs(p.visibility.subdomains, p.subdomains).join(', '), tableSearchQuery)"></div>
              <div v-else class="text-slate-500">—</div>
            </td>
            <td class="py-2 pr-4">
              <div v-if="p.urls?.length">
                <div v-for="u in p.urls" :key="u">
                  <a :href="u" target="_blank" class="underline" v-html="highlightText(u, tableSearchQuery)"></a>
                </div>
              </div>
              <div v-else class="text-slate-500">—</div>
            </td>
            <td class="py-2 pr-4">
              <Button variant="secondary" size="sm" @click="editInOurForm(p)">Настроить</Button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Показать ещё -->
    <ShowMorePagination
      :displayed="displayedItems.length"
      :total="tableFilteredItems.length"
      :has-more="hasMoreItems"
      :items-per-page="itemsPerPage"
      @show-more="currentPage = Math.min(totalPages, currentPage + 1)"
      @update:items-per-page="itemsPerPage = $event"
    />


    <!-- Модальное окно массовых настроек -->
    <Modal 
      v-model="bulkModalOpen"
      title="Массовые настройки видимости"
      size="lg"
    >
        
        <div class="mb-4">
          <label class="text-slate-600 mr-2">Правило:</label>
          <Select v-model="bulk.rule">
            <option value="all">Показывать везде</option>
            <option value="allow">Только выбранные</option>
            <option value="deny">Скрывать выбранные</option>
          </Select>
        </div>
        
        <div v-if="bulk.rule !== 'all'" class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600">Папки</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllBulkFolders"
                class="text-xs"
              >
                {{ allBulkFoldersSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="searchFolderQuery" 
              placeholder="Поиск папок..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredFolders.length }} из {{ allFolders.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="f in filteredFolders" :key="f.id" class="block mb-1">
                <input type="checkbox" :value="Number(f.id)" v-model="bulk.folders" /> <span v-html="highlightText(f.slug, searchFolderQuery)"></span>
              </label>
              <div v-if="filteredFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <div class="text-slate-600">Поддомены</div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleAllBulkSubdomains"
                class="text-xs"
              >
                {{ allBulkSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
              </Button>
            </div>
            <Input 
              v-model="searchSubdomainQuery" 
              placeholder="Поиск поддоменов..." 
              size="sm"
              class="w-full mb-2"
            />
            <div class="text-xs text-slate-500 mb-1">
              Показано: {{ filteredSubdomains.length }} из {{ allSubdomains.length }}
            </div>
            <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
              <label v-for="s in filteredSubdomains" :key="s.id" class="block mb-1">
                <input type="checkbox" :value="Number(s.id)" v-model="bulk.subdomains" /> <span v-html="highlightText(s.slug, searchSubdomainQuery)"></span>
              </label>
              <div v-if="filteredSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
            </div>
          </div>
        </div>

        <div class="mb-4 border-t pt-4">
          <div class="flex items-center justify-between mb-2">
            <label class="text-slate-600 font-medium">Страницы для применения ({{ bulk.selectedPageIds.length }} выбрано):</label>
            <Button variant="ghost" size="sm" @click="toggleSelectAllInBulk">{{ allBulkPagesSelected ? 'Снять все' : 'Выбрать все' }}</Button>
          </div>
          <Input 
            v-model="searchQuery" 
            placeholder="Поиск по названию..." 
            class="w-full mb-2"
          />
          <div class="text-xs text-slate-500 mb-1">
            Показано: {{ filteredItems.length }} из {{ filteredItemsAll.length }} найдено (всего {{ items.length }})
            <span v-if="filteredItemsAll.length > 10" class="text-orange-600"> — уточните поиск, чтобы увидеть все</span>
          </div>
          <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
            <label v-for="p in filteredItems" :key="p.id" class="block mb-1 hover:bg-slate-50">
              <input type="checkbox" :value="p.id" v-model="bulk.selectedPageIds" /> <span v-html="highlightText(p.title, searchQuery)"></span> <span class="text-slate-500 text-xs" v-html="'(' + highlightText(p.slug, searchQuery) + ')'"></span>
            </label>
            <div v-if="filteredItems.length === 0" class="text-slate-500 text-xs text-center py-2">Не найдено</div>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <Button variant="secondary" @click="closeBulkModal">Отмена</Button>
          <Button variant="primary" @click="applyBulk" :disabled="bulk.selectedPageIds.length === 0">
            Применить к выбранным ({{ bulk.selectedPageIds.length }})
          </Button>
        </div>
    </Modal>

    <!-- Модальное окно создания страницы -->
    <Modal 
      v-model="createModalOpen"
      title="Добавить страницу"
      size="lg"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Название</label>
          <Input 
            v-model="newPage.title" 
            placeholder="Введите название страницы..."
            class="w-full"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Слаг</label>
          <div class="flex items-center gap-2">
            <Input 
              v-model="newPage.slug" 
              placeholder="page-slug"
              class="flex-1"
              @focus="slugWasManuallyChanged = true"
              @input="slugWasManuallyChanged = true"
            />
            <button 
              @click="regenerateSlug"
              class="p-2 border rounded hover:bg-slate-100 transition-colors"
              title="Регенерировать слаг из названия"
              type="button"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
            </button>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Слаг автоматически генерируется из названия при вводе. Используйте кнопку ↻ для перегенерации из текущего названия.
          </p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Контент</label>
          <textarea 
            v-model="newPage.content" 
            placeholder="Введите содержимое страницы..."
            class="w-full border rounded px-3 py-2 h-32 resize-none"
          ></textarea>
        </div>

        <!-- SEO метаданные -->
        <div class="border-t pt-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-slate-700">SEO метаданные</h4>
            <span v-if="seoInfo && seoInfo.seoPlugin" class="text-xs text-slate-500">
              Синхронизация с {{ seoInfo.seoPlugin.name }}
            </span>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Title</label>
              <Input 
                v-model="newPage.seoTitle" 
                placeholder="Введите SEO заголовок..."
                class="w-full"
              />
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 50-60 символов</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Description</label>
              <textarea 
                v-model="newPage.seoDescription" 
                placeholder="Введите SEO описание..."
                class="w-full border rounded px-3 py-2 h-24 resize-none"
              ></textarea>
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 150-160 символов</p>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">Видимость страницы</label>
            <Select v-model="newPage.visibilityRule" class="w-full mb-4">
              <option value="all">Показывать везде</option>
              <option value="allow">Только выбранные</option>
              <option value="deny">Скрывать выбранные</option>
            </Select>
          </div>
          
          <div v-if="newPage.visibilityRule !== 'all'" class="grid grid-cols-2 gap-4">
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Папки</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllCreateFolders"
                  class="text-xs"
                >
                  {{ allCreateFoldersSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="createFolderSearch" 
                placeholder="Поиск папок..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredCreateFolders.length }} из {{ allFolders.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="f in filteredCreateFolders" :key="f.id" class="block mb-1">
                  <input type="checkbox" :value="Number(f.id)" v-model="newPage.folders" /> <span v-html="highlightText(f.slug, createFolderSearch)"></span>
                </label>
                <div v-if="filteredCreateFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Поддомены</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllCreateSubdomains"
                  class="text-xs"
                >
                  {{ allCreateSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="createSubdomainSearch" 
                placeholder="Поиск поддоменов..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredCreateSubdomains.length }} из {{ allSubdomains.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="s in filteredCreateSubdomains" :key="s.id" class="block mb-1">
                  <input type="checkbox" :value="Number(s.id)" v-model="newPage.subdomains" /> <span v-html="highlightText(s.slug, createSubdomainSearch)"></span>
                </label>
                <div v-if="filteredCreateSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="flex gap-2 justify-end mt-6">
        <Button variant="secondary" @click="closeCreateModal">Отмена</Button>
        <Button variant="primary" @click="createPage" :disabled="!newPage.title || creating">
          {{ creating ? 'Создание...' : 'Создать' }}
        </Button>
      </div>
    </Modal>

    <!-- Модальное окно редактирования страницы -->
    <Modal 
      v-model="editPageModalOpen"
      :title="`Редактирование: ${editPage.title || ''}`"
      size="lg"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Название</label>
          <Input 
            v-model="editPage.title" 
            placeholder="Введите название страницы..."
            class="w-full"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Слаг</label>
          <div class="flex items-center gap-2">
            <Input 
              v-model="editPage.slug" 
              placeholder="page-slug"
              class="flex-1"
              @focus="editSlugWasManuallyChanged = true"
              @input="editSlugWasManuallyChanged = true"
            />
            <button 
              @click="regenerateEditSlug"
              class="p-2 border rounded hover:bg-slate-100 transition-colors"
              title="Регенерировать слаг из названия"
              type="button"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
            </button>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Слаг автоматически генерируется из названия при вводе. Используйте кнопку ↻ для перегенерации из текущего названия.
          </p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Контент</label>
          <textarea 
            v-model="editPage.content" 
            placeholder="Введите содержимое страницы..."
            class="w-full border rounded px-3 py-2 h-32 resize-none"
          ></textarea>
        </div>

        <!-- SEO метаданные -->
        <div class="border-t pt-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-slate-700">SEO метаданные</h4>
            <span v-if="editSeoInfo && editSeoInfo.seoPlugin" class="text-xs text-slate-500">
              Синхронизация с {{ editSeoInfo.seoPlugin.name }}
            </span>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Title</label>
              <Input 
                v-model="editPage.seoTitle" 
                placeholder="Введите SEO заголовок..."
                class="w-full"
              />
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 50-60 символов</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">SEO Description</label>
              <textarea 
                v-model="editPage.seoDescription" 
                placeholder="Введите SEO описание..."
                class="w-full border rounded px-3 py-2 h-24 resize-none"
              ></textarea>
              <p class="text-xs text-slate-500 mt-1">Рекомендуемая длина: 150-160 символов</p>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">Видимость страницы</label>
            <Select v-model="editPage.visibilityRule" class="w-full mb-4">
              <option value="all">Показывать везде</option>
              <option value="allow">Только выбранные</option>
              <option value="deny">Скрывать выбранные</option>
            </Select>
          </div>
          
          <div v-if="editPage.visibilityRule !== 'all'" class="grid grid-cols-2 gap-4">
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Папки</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllEditFolders"
                  class="text-xs"
                >
                  {{ allEditFoldersSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="editFolderSearch" 
                placeholder="Поиск папок..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredEditFolders.length }} из {{ allFolders.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="f in filteredEditFolders" :key="f.id" class="block mb-1">
                  <input type="checkbox" :value="Number(f.id)" v-model="editPage.folders" /> <span v-html="highlightText(f.slug, editFolderSearch)"></span>
                </label>
                <div v-if="filteredEditFolders.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="text-slate-600 font-medium">Поддомены</div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleAllEditSubdomains"
                  class="text-xs"
                >
                  {{ allEditSubdomainsSelected ? 'Снять все' : 'Выбрать все' }}
                </Button>
              </div>
              <Input 
                v-model="editSubdomainSearch" 
                placeholder="Поиск поддоменов..." 
                size="sm"
                class="w-full mb-2"
              />
              <div class="text-xs text-slate-500 mb-1">
                Показано: {{ filteredEditSubdomains.length }} из {{ allSubdomains.length }}
              </div>
              <div class="border rounded p-2" style="height: 100px; overflow-y: auto;">
                <label v-for="s in filteredEditSubdomains" :key="s.id" class="block mb-1">
                  <input type="checkbox" :value="Number(s.id)" v-model="editPage.subdomains" /> <span v-html="highlightText(s.slug, editSubdomainSearch)"></span>
                </label>
                <div v-if="filteredEditSubdomains.length === 0" class="text-slate-500 text-xs">Не найдено</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="flex gap-2 justify-end mt-6">
        <Button variant="secondary" @click="closeEditPageModal">Отмена</Button>
        <Button variant="primary" @click="updatePage" :disabled="!editPage.title || updating">
          {{ updating ? 'Сохранение...' : 'Сохранить' }}
        </Button>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { inject, ref, onMounted, onUnmounted, reactive, computed, watch } from 'vue';
import { Button, Input, Select, Modal, ShowMorePagination } from '../components';
import { highlightText } from '../utils/highlight.js';
const boot = inject('boot');
const api = inject('api');
const items = ref([]);
const error = ref('');
const listUrl = `${boot.adminUrl}edit.php?post_type=multiregional_page`;
const newUrl = `${boot.adminUrl}post-new.php?post_type=multiregional_page`;
const editUrl = (id) => `${boot.adminUrl}post.php?post=${id}&action=edit`;
const bulkModalOpen = ref(false);
const searchQuery = ref('');
const selectedIds = ref([]);
const bulk = reactive({ rule: 'allow', folders: [], subdomains: [], selectedPageIds: [] });
const allFolders = ref([]);
const allSubdomains = ref([]);

const tableSearchQuery = ref('');
const tableFilterFolders = ref([]);
const tableFilterSubdomains = ref([]);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const filtersModalOpen = ref(false);
const tableFolderSearch = ref('');
const tableSubdomainSearch = ref('');
const bulkConfirmOpen = ref(false);
const createModalOpen = ref(false);
const creating = ref(false);
const createFolderSearch = ref('');
const createSubdomainSearch = ref('');
const slugWasManuallyChanged = ref(false);
const newPage = reactive({
  title: '',
  slug: '',
  content: '',
  visibilityRule: 'all',
  folders: [],
  subdomains: [],
  seoTitle: '',
  seoDescription: ''
});

const editMenuId = ref(null);
const editPageModalOpen = ref(false);
const editingPage = ref(null);
const updating = ref(false);
const editPage = reactive({
  id: null,
  title: '',
  slug: '',
  content: '',
  visibilityRule: 'all',
  folders: [],
  subdomains: [],
  seoTitle: '',
  seoDescription: ''
});
const editSeoInfo = ref(null);
const editFolderSearch = ref('');
const editSubdomainSearch = ref('');
const editSlugWasManuallyChanged = ref(false);

const seoInfo = ref(null);

// Функция транслитерации для создания слага
function transliterateToSlug(text) {
  if (!text) return '';
  
  const transliterationMap = {
    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
    'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
    'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
    'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch',
    'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
    'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'Yo',
    'Ж': 'Zh', 'З': 'Z', 'И': 'I', 'Й': 'Y', 'К': 'K', 'Л': 'L', 'М': 'M',
    'Н': 'N', 'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U',
    'Ф': 'F', 'Х': 'H', 'Ц': 'Ts', 'Ч': 'Ch', 'Ш': 'Sh', 'Щ': 'Sch',
    'Ъ': '', 'Ы': 'Y', 'Ь': '', 'Э': 'E', 'Ю': 'Yu', 'Я': 'Ya'
  };
  
  let slug = text;
  
  // Транслитерация
  for (const [cyr, lat] of Object.entries(transliterationMap)) {
    slug = slug.replace(new RegExp(cyr, 'g'), lat);
  }
  
  // Удаление всех символов кроме букв, цифр, пробелов и дефисов (с учетом Unicode)
  slug = slug.replace(/[^\p{L}\p{N}\s-]/gu, '');
  
  // Замена пробелов и множественных дефисов на один дефис
  slug = slug.replace(/\s+/g, '-').replace(/-+/g, '-');
  
  // Приведение к нижнему регистру
  slug = slug.toLowerCase();
  
  // Удаление дефисов в начале и конце
  slug = slug.replace(/^-+|-+$/g, '');
  
  return slug;
}

// Автоматическое заполнение слага из названия
watch(() => newPage.title, (newTitle, oldTitle) => {
  // Заполняем слаг только если:
  // 1. Слаг еще не был изменен вручную
  // 2. Название заполнено
  if (!slugWasManuallyChanged.value && newTitle) {
    // Вычисляем слаги
    const newSlug = transliterateToSlug(newTitle);
    const oldSlug = oldTitle ? transliterateToSlug(oldTitle) : '';
    
    // Обновляем слаг если:
    // - слаг пустой, или
    // - старый title был пустой (первый ввод), или
    // - текущий слаг совпадает с транслитерацией старого title (значит он был автоматически сгенерирован)
    if (!newPage.slug || !oldTitle || newPage.slug === oldSlug) {
      newPage.slug = newSlug;
    }
  }
});

// Отслеживание ручного изменения слага происходит через события @focus и @input на Input

onMounted(async () => {
  await load();
  await loadFolders();
  await loadSubdomains();
  window.addEventListener('click', handleClickOutside);
});
onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside);
  // Убираем обработчик закрытия меню, если он был добавлен
  document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
});

const tableFilteredItems = computed(() => {
  let result = items.value;
  
  // Поиск по названию/слагу/папкам/поддоменам
  if (tableSearchQuery.value.trim()) {
    const q = tableSearchQuery.value.toLowerCase();
    result = result.filter(p => {
      // Поиск по title и slug
      if (p.title.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)) {
        return true;
      }
      // Поиск по папкам
      const pFolders = mapFolderIdsToSlugs(p.visibility?.folders || [], p.folders || []);
      if (pFolders.some(f => f.toLowerCase().includes(q))) {
        return true;
      }
      // Поиск по поддоменам
      const pSubs = mapSubIdsToSlugs(p.visibility?.subdomains || [], p.subdomains || []);
      if (pSubs.some(s => s.toLowerCase().includes(q))) {
        return true;
      }
      // Поиск по URL
      if (p.urls && Array.isArray(p.urls)) {
        if (p.urls.some(u => String(u).toLowerCase().includes(q))) {
          return true;
        }
      }
      return false;
    });
  }
  
  // Фильтр по папкам
  const filterFolders = Array.isArray(tableFilterFolders.value) 
    ? tableFilterFolders.value.filter(f => f !== '' && f !== null && f !== undefined).map(Number) 
    : [];
  if (filterFolders.length > 0) {
    result = result.filter(p => {
      const pFolders = Array.isArray(p.visibility?.folders) ? p.visibility.folders.map(Number) : [];
      return filterFolders.some(fid => pFolders.includes(fid));
    });
  }
  
  // Фильтр по поддоменам
  const filterSubs = Array.isArray(tableFilterSubdomains.value) 
    ? tableFilterSubdomains.value.filter(s => s !== '' && s !== null && s !== undefined).map(Number) 
    : [];
  if (filterSubs.length > 0) {
    result = result.filter(p => {
      const pSubs = Array.isArray(p.visibility?.subdomains) ? p.visibility.subdomains.map(Number) : [];
      return filterSubs.some(sid => pSubs.includes(sid));
    });
  }
  
  return result;
});

const totalPages = computed(() => Math.max(1, Math.ceil(tableFilteredItems.value.length / itemsPerPage.value)));

// Отображаем накопительно ("Показать ещё")
const displayedItems = computed(() => {
  const end = currentPage.value * itemsPerPage.value;
  return tableFilteredItems.value.slice(0, end);
});
const hasMoreItems = computed(() => displayedItems.value.length < tableFilteredItems.value.length);

const allSelected = computed(() => {
  if (displayedItems.value.length === 0) return false;
  return displayedItems.value.every(p => selectedIds.value.includes(p.id));
});
const filteredItemsAll = computed(() => {
  if (!searchQuery.value.trim()) return items.value;
  const q = searchQuery.value.toLowerCase();
  return items.value.filter(p => 
    p.title.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)
  );
});
const filteredItems = computed(() => {
  return filteredItemsAll.value.slice(0, 10); // Показываем максимум 10 результатов
});
const searchFolderQuery = ref('');
const searchSubdomainQuery = ref('');
const filteredFolders = computed(() => {
  if (!searchFolderQuery.value.trim()) return allFolders.value;
  const q = searchFolderQuery.value.toLowerCase();
  return allFolders.value.filter(f => f.slug.toLowerCase().includes(q) || (f.name && f.name.toLowerCase().includes(q)));
});

const filteredSubdomains = computed(() => {
  if (!searchSubdomainQuery.value.trim()) return allSubdomains.value;
  const q = searchSubdomainQuery.value.toLowerCase();
  return allSubdomains.value.filter(s => s.slug.toLowerCase().includes(q) || (s.name && s.name.toLowerCase().includes(q)));
});

const allBulkFoldersSelected = computed(() => {
  if (!filteredFolders.value.length) return false;
  return filteredFolders.value.every(f => bulk.folders.includes(Number(f.id)));
});

const allBulkSubdomainsSelected = computed(() => {
  if (!filteredSubdomains.value.length) return false;
  return filteredSubdomains.value.every(s => bulk.subdomains.includes(Number(s.id)));
});

const allBulkPagesSelected = computed(() => {
  if (!filteredItems.value.length) return false;
  return filteredItems.value.every(p => bulk.selectedPageIds.includes(p.id));
});

const filteredTableFolders = computed(() => {
  if (!tableFolderSearch.value.trim()) return allFolders.value;
  const q = tableFolderSearch.value.toLowerCase();
  return allFolders.value.filter(f => f.slug.toLowerCase().includes(q) || (f.name && f.name.toLowerCase().includes(q)));
});

const filteredTableSubdomains = computed(() => {
  if (!tableSubdomainSearch.value.trim()) return allSubdomains.value;
  const q = tableSubdomainSearch.value.toLowerCase();
  return allSubdomains.value.filter(s => s.slug.toLowerCase().includes(q) || (s.name && s.name.toLowerCase().includes(q)));
});

const allTableFoldersSelected = computed(() => {
  if (!filteredTableFolders.value.length) return false;
  return filteredTableFolders.value.every(f => tableFilterFolders.value.includes(Number(f.id)));
});

const allTableSubdomainsSelected = computed(() => {
  if (!filteredTableSubdomains.value.length) return false;
  return filteredTableSubdomains.value.every(s => tableFilterSubdomains.value.includes(Number(s.id)));
});

// Фильтрация для попапа создания
const filteredCreateFolders = computed(() => {
  if (!createFolderSearch.value.trim()) return allFolders.value;
  const q = createFolderSearch.value.toLowerCase();
  return allFolders.value.filter(f => 
    (f.slug || '').toLowerCase().includes(q) ||
    (f.nominative || '').toLowerCase().includes(q) ||
    (f.dative || '').toLowerCase().includes(q) ||
    (f.genitive || '').toLowerCase().includes(q)
  );
});

const filteredCreateSubdomains = computed(() => {
  if (!createSubdomainSearch.value.trim()) return allSubdomains.value;
  const q = createSubdomainSearch.value.toLowerCase();
  return allSubdomains.value.filter(s => 
    (s.slug || '').toLowerCase().includes(q) ||
    (s.nominative || '').toLowerCase().includes(q) ||
    (s.dative || '').toLowerCase().includes(q) ||
    (s.genitive || '').toLowerCase().includes(q)
  );
});

const allCreateFoldersSelected = computed(() => {
  if (newPage.visibilityRule === 'all' || !filteredCreateFolders.value.length) return false;
  return filteredCreateFolders.value.every(f => newPage.folders.includes(Number(f.id)));
});

const allCreateSubdomainsSelected = computed(() => {
  if (newPage.visibilityRule === 'all' || !filteredCreateSubdomains.value.length) return false;
  return filteredCreateSubdomains.value.every(s => newPage.subdomains.includes(Number(s.id)));
});

async function load() {
  try {
    const res = await fetch(`${boot.restUrl}mr-ml/v1/pages`, { headers: { 'X-WP-Nonce': boot.nonce } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const rows = await res.json();
    rows.forEach(r => {
      if (!r.folders) r.folders = [];
      if (!r.subdomains) r.subdomains = [];
      r._draft = {
        rule: r.visibility?.rule || 'allow',
        folders: Array.isArray(r.visibility?.folders) ? r.visibility.folders.map(Number) : [],
        subdomains: Array.isArray(r.visibility?.subdomains) ? r.visibility.subdomains.map(Number) : [],
      };
    });
    items.value = rows;
    if (rows.length > 0) {
      allFolders.value = rows[0].folders || [];
      allSubdomains.value = rows[0].subdomains || [];
    }
  } catch (e) {
    error.value = String(e.message || e);
  }
}

function mapFolderIdsToSlugs(ids, folders){
  const map = new Map((folders||[]).map(f => [Number(f.id), f.slug]));
  return (ids||[]).map(id => map.get(Number(id))).filter(Boolean);
}
function mapSubIdsToSlugs(ids, subs){
  const map = new Map((subs||[]).map(s => [Number(s.id), s.slug]));
  return (ids||[]).map(id => map.get(Number(id))).filter(Boolean);
}

// Используем функцию подсветки из утилит

function toggleSelectAll() {
  if (allSelected.value) {
    // Удаляем только текущие страницы из пагинации
    displayedItems.value.forEach(p => {
      const idx = selectedIds.value.indexOf(p.id);
      if (idx >= 0) selectedIds.value.splice(idx, 1);
    });
  } else {
    // Добавляем текущие страницы из пагинации
    displayedItems.value.forEach(p => {
      if (!selectedIds.value.includes(p.id)) {
        selectedIds.value.push(p.id);
      }
    });
  }
}

// Сбрасываем страницу при изменении фильтров или количества на странице
watch([tableSearchQuery, tableFilterFolders, tableFilterSubdomains, itemsPerPage], () => {
  currentPage.value = 1;
});

function handleFolderCheckboxChange(event) {
  // v-model автоматически обрабатывает toggle, функция нужна только для дополнительной логики если потребуется
  // Сбрасываем страницу при изменении фильтра
  currentPage.value = 1;
}

function handleSubdomainCheckboxChange(event) {
  // v-model автоматически обрабатывает toggle, функция нужна только для дополнительной логики если потребуется
  // Сбрасываем страницу при изменении фильтра
  currentPage.value = 1;
}

function toggleAllTableFolders() {
  const filteredIds = filteredTableFolders.value.map(f => Number(f.id));
  if (allTableFoldersSelected.value) {
    // Снимаем только отфильтрованные
    tableFilterFolders.value = tableFilterFolders.value.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!tableFilterFolders.value.includes(id)) {
        tableFilterFolders.value.push(id);
      }
    });
  }
}

function toggleAllTableSubdomains() {
  const filteredIds = filteredTableSubdomains.value.map(s => Number(s.id));
  if (allTableSubdomainsSelected.value) {
    // Снимаем только отфильтрованные
    tableFilterSubdomains.value = tableFilterSubdomains.value.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!tableFilterSubdomains.value.includes(id)) {
        tableFilterSubdomains.value.push(id);
      }
    });
  }
}

function closeFiltersModal() {
  filtersModalOpen.value = false;
  tableFolderSearch.value = '';
  tableSubdomainSearch.value = '';
}



function toggleAllBulkFolders() {
  const filteredIds = filteredFolders.value.map(f => Number(f.id));
  if (allBulkFoldersSelected.value) {
    // Снимаем только отфильтрованные
    bulk.folders = bulk.folders.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!bulk.folders.includes(id)) {
        bulk.folders.push(id);
      }
    });
  }
}

function toggleAllBulkSubdomains() {
  const filteredIds = filteredSubdomains.value.map(s => Number(s.id));
  if (allBulkSubdomainsSelected.value) {
    // Снимаем только отфильтрованные
    bulk.subdomains = bulk.subdomains.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!bulk.subdomains.includes(id)) {
        bulk.subdomains.push(id);
      }
    });
  }
}

function toggleSelectAllInBulk() {
  const filteredIds = filteredItems.value.map(p => p.id);
  if (allBulkPagesSelected.value) {
    // Снимаем только отфильтрованные
    bulk.selectedPageIds = bulk.selectedPageIds.filter(id => !filteredIds.includes(id));
  } else {
    // Добавляем отфильтрованные, не дублируя
    filteredIds.forEach(id => {
      if (!bulk.selectedPageIds.includes(id)) {
        bulk.selectedPageIds.push(id);
      }
    });
  }
}

function openBulkModal() {
  bulk.selectedPageIds = selectedIds.value.length > 0 ? [...selectedIds.value] : [];
  searchQuery.value = '';
  searchFolderQuery.value = '';
  searchSubdomainQuery.value = '';
  bulkModalOpen.value = true;
}

function closeBulkModal() {
  bulkModalOpen.value = false;
  bulk.selectedPageIds = [];
  searchQuery.value = '';
  searchFolderQuery.value = '';
  searchSubdomainQuery.value = '';
  bulkConfirmOpen.value = false;
}

function toggleAllCreateFolders() {
  const filteredIds = filteredCreateFolders.value.map(f => Number(f.id));
  if (allCreateFoldersSelected.value) {
    newPage.folders = newPage.folders.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(newPage.folders);
    filteredIds.forEach(id => existing.add(id));
    newPage.folders = Array.from(existing);
  }
}

function toggleAllCreateSubdomains() {
  const filteredIds = filteredCreateSubdomains.value.map(s => Number(s.id));
  if (allCreateSubdomainsSelected.value) {
    newPage.subdomains = newPage.subdomains.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(newPage.subdomains);
    filteredIds.forEach(id => existing.add(id));
    newPage.subdomains = Array.from(existing);
  }
}

function showMessage(msg, type = 'success') {
  window.dispatchEvent(new CustomEvent('mr-ml-notify', { detail: { message: msg, type } }));
}

function resetFilters() {
  tableSearchQuery.value = '';
  tableFilterFolders.value = [];
  tableFilterSubdomains.value = [];
  tableFolderSearch.value = '';
  tableSubdomainSearch.value = '';
}

function regenerateSlug() {
  if (newPage.title) {
    newPage.slug = transliterateToSlug(newPage.title);
    slugWasManuallyChanged.value = false; // Разрешаем автообновление после регенерации
  }
}

async function openCreateModal() {
  newPage.title = '';
  newPage.slug = '';
  newPage.content = '';
  newPage.visibilityRule = 'all';
  newPage.folders = [];
  newPage.subdomains = [];
  newPage.seoTitle = '';
  newPage.seoDescription = '';
  createFolderSearch.value = '';
  createSubdomainSearch.value = '';
  slugWasManuallyChanged.value = false;
  
  // Загружаем информацию о SEO-режиме
  try {
    const seoData = await api.getSeo();
    seoInfo.value = {
      activeSeoPlugin: seoData.activeSeoPlugin || '',
      seoPlugin: seoData.detectedSeoPlugins?.find(p => p.type === seoData.activeSeoPlugin) || null
    };
  } catch (e) {
    console.warn('Failed to load SEO info:', e);
    seoInfo.value = { activeSeoPlugin: '', seoPlugin: null };
  }
  
  createModalOpen.value = true;
}

function closeCreateModal() {
  createModalOpen.value = false;
  newPage.title = '';
  newPage.slug = '';
  newPage.content = '';
  newPage.visibilityRule = 'all';
  newPage.folders = [];
  newPage.subdomains = [];
  newPage.seoTitle = '';
  newPage.seoDescription = '';
  createFolderSearch.value = '';
  createSubdomainSearch.value = '';
  slugWasManuallyChanged.value = false;
  seoInfo.value = null;
}

async function createPage() {
  if (!newPage.title || creating.value) return;
  
  // Проверка уникальности слага перед созданием
  if (newPage.slug) {
    const existingSlug = items.value.find(p => p.slug === newPage.slug);
    if (existingSlug) {
      showMessage(`Слаг "${newPage.slug}" уже используется на странице "${existingSlug.title}". Используйте другой слаг.`, 'error');
      creating.value = false;
      return;
    }
  }
  
  creating.value = true;
  try {
    const response = await api.createPage({
      title: newPage.title,
      slug: newPage.slug || undefined,
      content: newPage.content,
      status: 'publish' // Всегда публикуем страницу
    });
    
    // Устанавливаем видимость, если она была задана
    if (response.id && newPage.visibilityRule !== 'all') {
      try {
        await api.setVisibility(response.id, {
          rule: newPage.visibilityRule,
          folders: newPage.folders,
          subdomains: newPage.subdomains
        });
      } catch (e) {
        console.warn('Failed to set visibility:', e);
      }
    }
    
    // Сохраняем SEO-метаданные (всегда сохраняем, синхронизация произойдет на бэкенде)
    if (response.id && (newPage.seoTitle || newPage.seoDescription)) {
      try {
        await api.setPageSeo(response.id, {
          title: newPage.seoTitle || '',
          description: newPage.seoDescription || ''
        });
      } catch (e) {
        console.warn('Failed to set SEO meta:', e);
      }
    }
    
    showMessage('Страница успешно создана', 'success');
    closeCreateModal();
    await load();
  } catch (e) {
    showMessage('Ошибка создания страницы: ' + String(e.message || e), 'error');
  } finally {
    creating.value = false;
  }
}


async function applyBulk() {
  if (bulk.selectedPageIds.length === 0) {
    showMessage('Выберите хотя бы одну страницу', 'error');
    return;
  }
  bulkConfirmOpen.value = true;
}

async function confirmBulk() {
  bulkConfirmOpen.value = false;
  
  let saved = 0;
  let errors = 0;
  for (const id of bulk.selectedPageIds) {
    const payload = { rule: bulk.rule, folders: bulk.folders, subdomains: bulk.subdomains };
    const res = await fetch(`${boot.restUrl}mr-ml/v1/visibility/${id}`, {
      method: 'POST',
      headers: { 'X-WP-Nonce': boot.nonce, 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    if (res.ok) saved++; else errors++;
  }
  showMessage(`Готово: ${saved} сохранено, ${errors} ошибок`, saved > 0 ? 'success' : 'error');
  closeBulkModal();
  await load();
  selectedIds.value = [];
}

// Меню выбора способа редактирования
function showEditMenu(page, e) {
  e?.stopPropagation();
  // Закрываем предыдущее меню, если открыто другое
  if (editMenuId.value !== page.id) {
    editMenuId.value = page.id;
    // Добавляем обработчик для закрытия при клике вне меню
    // Используем setTimeout, чтобы не закрыть меню сразу же
    setTimeout(() => {
      document.addEventListener('click', closeEditMenuOnOutsideClick, true);
    }, 0);
  } else {
    editMenuId.value = null;
    document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  }
}

function closeEditMenuOnOutsideClick(e) {
  if (!editMenuId.value) {
    document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
    return;
  }
  
  // Ищем меню и кнопку открытия для текущего ID
  const menuElement = document.querySelector(`[data-edit-menu-id="${editMenuId.value}"]`);
  const buttonElement = document.querySelector(`[data-edit-menu-button][data-page-id="${editMenuId.value}"]`);
  
  // Проверяем, был ли клик внутри меню или на кнопке
  const clickedInsideMenu = menuElement && menuElement.contains(e.target);
  const clickedOnButton = buttonElement && (buttonElement === e.target || buttonElement.contains(e.target));
  
  // Если клик был вне меню и не на кнопке, закрываем
  if (!clickedInsideMenu && !clickedOnButton) {
    editMenuId.value = null;
    document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  }
}

function editInWp(page) {
  editMenuId.value = null;
  document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  window.open(editUrl(page.id), '_blank');
}

async function editInOurForm(page) {
  editMenuId.value = null;
  document.removeEventListener('click', closeEditMenuOnOutsideClick, true);
  editingPage.value = page;
  
  try {
    // Загружаем данные страницы
    const pageData = await api.getPage(page.id);
    editPage.id = pageData.id;
    editPage.title = pageData.title?.rendered || '';
    editPage.slug = pageData.slug || '';
    editPage.content = pageData.content?.rendered || '';
    
    // Загружаем видимость
    const visibility = await api.getVisibility(page.id);
    editPage.visibilityRule = visibility.rule || 'all';
    editPage.folders = visibility.folders || [];
    editPage.subdomains = visibility.subdomains || [];
    
    // Загружаем SEO
    const seoData = await api.getPageSeo(page.id);
    editPage.seoTitle = seoData.title || '';
    editPage.seoDescription = seoData.description || '';
    
    // Загружаем информацию о SEO-режиме
    const seoSettings = await api.getSeo();
    editSeoInfo.value = {
      activeSeoPlugin: seoSettings.activeSeoPlugin || '',
      seoPlugin: seoSettings.detectedSeoPlugins?.find(p => p.type === seoSettings.activeSeoPlugin) || null
    };
    
    editFolderSearch.value = '';
    editSubdomainSearch.value = '';
    editSlugWasManuallyChanged.value = false;
    
    editPageModalOpen.value = true;
  } catch (e) {
    showMessage('Ошибка загрузки страницы: ' + String(e.message || e), 'error');
  }
}

function closeEditPageModal() {
  editPageModalOpen.value = false;
  editingPage.value = null;
  editPage.id = null;
  editPage.title = '';
  editPage.slug = '';
  editPage.content = '';
  editPage.visibilityRule = 'all';
  editPage.folders = [];
  editPage.subdomains = [];
  editPage.seoTitle = '';
  editPage.seoDescription = '';
  editFolderSearch.value = '';
  editSubdomainSearch.value = '';
  editSlugWasManuallyChanged.value = false;
  editSeoInfo.value = null;
}

async function updatePage() {
  if (!editPage.title || updating.value || !editPage.id) return;
  
  updating.value = true;
  try {
    // Обновляем основные данные страницы
    await api.updatePage(editPage.id, {
      title: editPage.title,
      slug: editPage.slug || undefined,
      content: editPage.content,
      status: 'publish'
    });
    
    // Обновляем видимость
    await api.setVisibility(editPage.id, {
      rule: editPage.visibilityRule,
      folders: editPage.folders,
      subdomains: editPage.subdomains
    });
    
    // Сохраняем SEO-метаданные
    if (editPage.seoTitle || editPage.seoDescription) {
      await api.setPageSeo(editPage.id, {
        title: editPage.seoTitle || '',
        description: editPage.seoDescription || ''
      });
    }
    
    showMessage('Страница успешно обновлена', 'success');
    closeEditPageModal();
    await load();
  } catch (e) {
    showMessage('Ошибка обновления страницы: ' + String(e.message || e), 'error');
  } finally {
    updating.value = false;
  }
}

function regenerateEditSlug() {
  if (editPage.title) {
    editPage.slug = transliterateToSlug(editPage.title);
    editSlugWasManuallyChanged.value = false;
  }
}

const filteredEditFolders = computed(() => {
  if (!editFolderSearch.value) return allFolders.value;
  const q = editFolderSearch.value.toLowerCase();
  return allFolders.value.filter(f => f.slug.toLowerCase().includes(q) || f.nominative?.toLowerCase().includes(q) || f.dative?.toLowerCase().includes(q) || f.genitive?.toLowerCase().includes(q));
});

const filteredEditSubdomains = computed(() => {
  if (!editSubdomainSearch.value) return allSubdomains.value;
  const q = editSubdomainSearch.value.toLowerCase();
  return allSubdomains.value.filter(s => s.slug.toLowerCase().includes(q) || s.nominative?.toLowerCase().includes(q) || s.dative?.toLowerCase().includes(q) || s.genitive?.toLowerCase().includes(q));
});

const allEditFoldersSelected = computed(() => {
  if (filteredEditFolders.value.length === 0) return false;
  return filteredEditFolders.value.every(f => editPage.folders.includes(Number(f.id)));
});

const allEditSubdomainsSelected = computed(() => {
  if (filteredEditSubdomains.value.length === 0) return false;
  return filteredEditSubdomains.value.every(s => editPage.subdomains.includes(Number(s.id)));
});

function toggleAllEditFolders() {
  const filteredIds = filteredEditFolders.value.map(f => Number(f.id));
  if (allEditFoldersSelected.value) {
    editPage.folders = editPage.folders.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(editPage.folders);
    filteredIds.forEach(id => existing.add(id));
    editPage.folders = Array.from(existing);
  }
}

function toggleAllEditSubdomains() {
  const filteredIds = filteredEditSubdomains.value.map(s => Number(s.id));
  if (allEditSubdomainsSelected.value) {
    editPage.subdomains = editPage.subdomains.filter(id => !filteredIds.includes(id));
  } else {
    const existing = new Set(editPage.subdomains);
    filteredIds.forEach(id => existing.add(id));
    editPage.subdomains = Array.from(existing);
  }
}

// Watch для автослага редактирования
watch(() => editPageModalOpen.value, (isOpen) => {
  if (isOpen && editPage.title) {
    let stopWatch = null;
    stopWatch = watch(() => editPage.title, (newTitle, oldTitle) => {
      if (!editSlugWasManuallyChanged.value) {
        const newSlug = transliterateToSlug(newTitle);
        if (!editPage.slug || editPage.slug === transliterateToSlug(oldTitle || '')) {
          editPage.slug = newSlug;
        }
      }
    });
    editPage._stopWatch = stopWatch;
  } else if (!isOpen && editPage._stopWatch) {
    editPage._stopWatch();
    editPage._stopWatch = null;
  }
});

</script>