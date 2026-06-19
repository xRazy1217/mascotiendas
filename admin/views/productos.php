<div x-show="seccion==='productos'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Productos</h2>
    <button @click="abrirFormProducto(null)"
            class="px-4 py-2 bg-mt-orange text-white rounded-xl font-bold text-sm hover:bg-orange-500 transition-colors">
      <i class="fas fa-plus mr-1"></i> Nuevo
    </button>
  </div>

  <div class="flex gap-3 mb-4 flex-wrap">
    <input x-model="prodQ" @input.debounce.400ms="prodPagina=1;cargarProductos()"
           placeholder="Buscar..." class="flex-grow min-w-[150px] px-4 py-2 rounded-xl border border-mt-cream focus:outline-none text-sm font-bold">
    <select x-model="prodOrden" @change="prodPagina=1;cargarProductos()"
            class="px-4 py-2 rounded-xl border border-mt-cream focus:outline-none text-sm font-bold bg-white text-mt-brown">
      <option value="id_desc">Más recientes</option>
      <option value="id_asc">Más antiguos</option>
      <option value="precio_asc">Precio: menor a mayor</option>
      <option value="precio_desc">Precio: mayor a menor</option>
      <option value="nombre_asc">Nombre: A-Z</option>
      <option value="nombre_desc">Nombre: Z-A</option>
      <option value="stock_asc">Stock: menor a mayor</option>
      <option value="stock_desc">Stock: mayor a menor</option>
    </select>
    <div class="flex gap-1">
      <button @click="prodFiltroStock='';prodPagina=1;cargarProductos()"
              :class="prodFiltroStock===''?'bg-mt-orange text-white':'bg-white text-mt-brown border border-mt-cream'"
              class="px-3 py-2 rounded-xl font-bold text-xs">Todos</button>
      <button @click="prodFiltroStock='sin';prodPagina=1;cargarProductos()"
              :class="prodFiltroStock==='sin'?'bg-mt-orange text-white':'bg-white text-mt-brown border border-mt-cream'"
              class="px-3 py-2 rounded-xl font-bold text-xs">Sin stock</button>
      <button @click="prodFiltroStock='bajo';prodPagina=1;cargarProductos()"
              :class="prodFiltroStock==='bajo'?'bg-mt-orange text-white':'bg-white text-mt-brown border border-mt-cream'"
              class="px-3 py-2 rounded-xl font-bold text-xs">Bajo stock</button>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-center px-4 py-3 w-12">
            <input type="checkbox" 
                   @change="selectAllProducts($event)" 
                   :checked="selectedProducts.length === listaProductos.length && listaProductos.length > 0"
                   class="w-4 h-4 accent-mt-orange rounded cursor-pointer">
          </th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Producto</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Precio</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Stock</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="p in listaProductos" :key="p.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="text-center px-4 py-3 w-12">
              <input type="checkbox" 
                     :checked="selectedProducts.includes(p.id)" 
                     @change="toggleProductSelection(p.id)"
                     class="w-4 h-4 accent-mt-orange rounded cursor-pointer">
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img :src="getProductImage(p.imagen)" class="w-10 h-10 object-cover rounded-lg flex-shrink-0">
                <div>
                  <p class="font-bold text-mt-brown line-clamp-1" x-text="p.nombre"></p>
                  <p class="text-xs text-slate-400" x-text="'ID: '+p.id"></p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 hidden md:table-cell font-black text-mt-brown" x-text="formatPrecio(p.precio_normal)"></td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span :class="p.en_stock?'bg-green-100 text-green-700':'bg-red-100 text-red-600'"
                    class="px-2 py-0.5 rounded-full text-xs font-black uppercase"
                    x-text="p.en_stock?'En stock':'Sin stock'"></span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="abrirFormProducto(p)"
                        class="px-3 py-1.5 bg-mt-cream text-mt-brown rounded-lg font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="eliminarProducto(p.id)"
                        class="px-3 py-1.5 bg-red-50 text-red-500 rounded-lg font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaProductos.length===0" class="text-center py-12 text-slate-400 italic">
      No hay productos
    </div>
  </div>

  <!-- Paginación -->
  <div x-show="prodPaginas>1" class="flex justify-center gap-2 mt-4">
    <button @click="prodPagina--;cargarProductos()" :disabled="prodPagina<=1"
            class="px-4 py-2 rounded-xl bg-mt-cream font-black text-mt-brown disabled:opacity-40">
      <i class="fas fa-chevron-left"></i>
    </button>
    <span class="px-4 py-2 font-bold text-mt-brown" x-text="prodPagina+' / '+prodPaginas"></span>
    <button @click="prodPagina++;cargarProductos()" :disabled="prodPagina>=prodPaginas"
            class="px-4 py-2 rounded-xl bg-mt-cream font-black text-mt-brown disabled:opacity-40">
      <i class="fas fa-chevron-right"></i>
    </button>
  </div>

  <!-- Modal Formulario Producto -->
  <div x-show="formProducto" x-cloak @click.self="formProducto=null"
       class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-[2rem] w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-black text-mt-brown" x-text="formProducto?.id?'Editar Producto':'Nuevo Producto'"></h3>
        <button @click="formProducto=null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
      </div>

      <template x-if="formProducto">
        <div class="space-y-4">
          <input x-model="formProducto.nombre" required placeholder="Nombre del producto *"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <div class="grid grid-cols-2 gap-3">
            <input x-model="formProducto.precio_normal" type="number" required placeholder="Precio normal *"
                   class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            <input x-model="formProducto.precio_rebajado" type="number" placeholder="Precio rebajado"
                   class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          </div>
          <input x-model="formProducto.sku" placeholder="SKU (opcional)"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <textarea x-model="formProducto.descripcion_corta" placeholder="Descripción corta" rows="2"
                    class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm resize-none"></textarea>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
              <input type="checkbox" x-model="formProducto.en_stock" class="w-4 h-4 accent-mt-orange"> En stock
            </label>
            <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
              <input type="checkbox" x-model="formProducto.activo" class="w-4 h-4 accent-mt-orange"> Activo
            </label>
          </div>

          <!-- Categorías -->
          <div class="border border-mt-cream rounded-xl p-4 bg-slate-50/50">
            <p class="font-black text-mt-brown text-sm mb-2">Categorías de este producto</p>
            <div class="grid grid-cols-2 gap-2 max-h-[140px] overflow-y-auto pr-1">
              <template x-for="cat in listaCategorias" :key="cat.id">
                <label class="flex items-center gap-2 font-bold text-xs cursor-pointer hover:bg-slate-100 p-1 rounded-lg">
                  <input type="checkbox" :value="cat.id" 
                         :checked="formProducto.categorias && formProducto.categorias.includes(parseInt(cat.id))"
                         @change="
                           if ($event.target.checked) {
                             if (!formProducto.categorias) formProducto.categorias = [];
                             formProducto.categorias.push(parseInt(cat.id));
                           } else {
                             formProducto.categorias = formProducto.categorias.filter(id => id != cat.id);
                           }
                         "
                         class="w-4 h-4 accent-mt-orange rounded cursor-pointer">
                  <span x-text="cat.nombre" class="text-mt-brown"></span>
                </label>
              </template>
            </div>
            <p x-show="!listaCategorias.length" class="text-xs text-slate-400 italic text-center py-2">
              Sin categorías. Cárgalas desde la sección de Categorías.
            </p>
          </div>

          <!-- Imágenes -->
          <div x-show="formProducto.id">
            <p class="font-black text-mt-brown text-sm mb-2">Imágenes</p>
            <div class="flex flex-wrap gap-2 mb-3">
              <template x-for="img in formProducto.imagenes||[]" :key="img.id">
                <div class="relative group">
                  <img :src="getProductImage(img.url)" class="w-20 h-20 object-cover rounded-xl border-2 border-mt-cream shadow-sm">
                  <button @click="eliminarImagen(img.id)"
                          class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600 transition-colors">
                    <i class="fas fa-times"></i>
                  </button>
                  <button @click="abrirModalRecorte(img)"
                          class="absolute -bottom-1 -right-1 w-6 h-6 bg-mt-orange text-white rounded-full text-xs flex items-center justify-center hover:bg-orange-600 shadow-sm transition-colors"
                          title="Ajustar recorte y zoom">
                    <i class="fas fa-crop-alt"></i>
                  </button>
                </div>
              </template>
            </div>
            <label class="flex items-center gap-2 px-4 py-2 bg-mt-cream rounded-xl cursor-pointer hover:bg-mt-orange hover:text-white transition-colors font-bold text-sm w-fit">
              <i class="fas fa-upload"></i> Subir imagen
              <input type="file" accept="image/*" class="hidden" @change="subirImagen($event)">
            </label>
          </div>

          <!-- Variantes -->
          <div x-show="formProducto.id" class="border border-mt-cream rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
              <p class="font-black text-mt-brown text-sm">Variantes</p>
              <button @click="abrirFormVariante(null)"
                      class="px-3 py-1.5 bg-mt-orange text-white rounded-lg font-bold text-xs hover:bg-orange-500 transition-colors">
                <i class="fas fa-plus mr-1"></i> Agregar
              </button>
            </div>
            <div class="space-y-2">
              <template x-for="v in formProducto.variantes||[]" :key="v.id">
                <div class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl">
                  <div class="flex-grow">
                    <p class="text-xs font-black text-mt-brown" x-text="v.label||'Sin atributos'" ></p>
                    <p class="text-xs text-slate-400" x-text="formatPrecio(v.precio_rebajado||v.precio_normal)+' · Stock: '+v.stock"></p>
                  </div>
                  <span :class="v.en_stock?'bg-green-100 text-green-700':'bg-red-100 text-red-600'"
                        class="text-[10px] font-black px-2 py-0.5 rounded-full"
                        x-text="v.en_stock?'Stock':'Agotado'"></span>
                  <button @click="abrirFormVariante(v)"
                          class="px-2 py-1 bg-mt-cream text-mt-brown rounded-lg text-xs hover:bg-mt-orange hover:text-white transition-colors">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="eliminarVariante(v.id)"
                          class="px-2 py-1 bg-red-50 text-red-500 rounded-lg text-xs hover:bg-red-500 hover:text-white transition-colors">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </template>
              <p x-show="!formProducto.variantes?.length" class="text-xs text-slate-400 italic text-center py-2">
                Sin variantes — el producto usa precio y stock principal
              </p>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button @click="guardarProducto()"
                    class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
              Guardar
            </button>
            <button @click="formProducto=null"
                    class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
              Cancelar
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>

<!-- Modal Variante -->
<div x-show="formVariante" x-cloak @click.self="formVariante=null"
     class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4 backdrop-blur-sm">
  <div class="bg-white rounded-[2rem] w-full max-w-lg shadow-2xl p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-black text-mt-brown" x-text="formVariante?.id ? 'Editar Variante' : 'Nueva Variante'"></h3>
      <button @click="formVariante=null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
    </div>
    <template x-if="formVariante">
      <div class="space-y-3">
        <!-- Atributos -->
        <div>
          <p class="font-black text-mt-brown text-xs uppercase mb-2">Atributos de esta variante</p>
          <template x-for="atrib in atributos" :key="atrib.id">
            <div class="mb-2">
              <p class="text-xs font-bold text-slate-500 mb-1" x-text="atrib.nombre"></p>
              <div class="flex flex-wrap gap-1">
                <template x-for="val in atrib.valores" :key="val.id">
                  <button @click="toggleAtribVal(val.id)"
                          :class="formVariante.atributo_valores.includes(val.id) ? 'bg-mt-orange text-white' : 'bg-mt-cream text-mt-brown'"
                          class="px-3 py-1 rounded-full text-xs font-bold hover:bg-mt-orange hover:text-white transition-colors"
                          x-text="val.valor"></button>
                </template>
              </div>
            </div>
          </template>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input x-model="formVariante.precio_normal" type="number" placeholder="Precio normal *"
                 class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <input x-model="formVariante.precio_rebajado" type="number" placeholder="Precio rebajado"
                 class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input x-model="formVariante.stock" type="number" placeholder="Stock"
                 class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <input x-model="formVariante.sku" placeholder="SKU variante"
                 class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
        </div>
        <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
          <input type="checkbox" x-model="formVariante.en_stock" class="w-4 h-4 accent-mt-orange"> En stock
        </label>
        <div class="flex gap-3 pt-2">
          <button @click="guardarVariante()"
                  class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
            Guardar Variante
          </button>
          <button @click="formVariante=null"
                  class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
            Cancelar
          </button>
        </div>
      </div>
    </template>
  </div>
</div>

<!-- Modal de Recorte de Imagen -->
<div x-show="modalRecorte" x-cloak @click.self="modalRecorte=null"
     class="fixed inset-0 bg-black/60 z-[70] flex items-center justify-center p-4 backdrop-blur-sm">
  <div class="bg-white rounded-[2rem] w-full max-w-xl shadow-2xl p-6 flex flex-col max-h-[90vh]">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-black text-mt-brown uppercase">Ajustar Recorte e Imagen</h3>
      <button @click="modalRecorte=null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
    </div>
    
    <template x-if="modalRecorte && recorteConfig">
      <div class="space-y-4 flex-grow overflow-y-auto pr-1">
        <!-- Tabs de Visualización -->
        <div class="flex border-b border-mt-cream">
          <button @click="recorteTab='catalogo'"
                  :class="recorteTab==='catalogo'?'border-mt-orange text-mt-orange font-black border-b-2':'text-slate-400 font-bold border-b border-transparent'"
                  class="flex-1 py-2 text-center text-xs uppercase tracking-wider">
            Catálogo
          </button>
          <button @click="recorteTab='detalle'"
                  :class="recorteTab==='detalle'?'border-mt-orange text-mt-orange font-black border-b-2':'text-slate-400 font-bold border-b border-transparent'"
                  class="flex-1 py-2 text-center text-xs uppercase tracking-wider">
            Detalle
          </button>
          <button @click="recorteTab='miniatura'"
                  :class="recorteTab==='miniatura'?'border-mt-orange text-mt-orange font-black border-b-2':'text-slate-400 font-bold border-b border-transparent'"
                  class="flex-1 py-2 text-center text-xs uppercase tracking-wider">
            Miniatura
          </button>
        </div>
        
        <!-- Contenedor de Previsualización Centrado -->
        <div class="bg-slate-50 rounded-2xl p-6 flex items-center justify-center min-h-[280px]">
          <!-- Caja de la Vista dependiente de la pestaña activa -->
          <div :class="{
                 'w-64 h-48 rounded-2xl': recorteTab==='catalogo',
                 'w-64 h-64 rounded-3xl': recorteTab==='detalle',
                 'w-16 h-16 rounded-xl': recorteTab==='miniatura'
               }"
               class="bg-mt-cream border-2 border-dashed border-slate-300 relative overflow-hidden transition-all duration-300 shadow-inner flex items-center justify-center">
            
            <img :src="getProductImage(modalRecorte.url)"
                 class="absolute w-full h-full transition-transform duration-100"
                 :style="obtenerEstiloPrevisualizacion()">
          </div>
        </div>
        
        <!-- Controles -->
        <div class="space-y-3 p-1">
          <!-- Modo de Ajuste (object-fit) -->
          <div>
            <span class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-2">Modo de ajuste</span>
            <div class="flex gap-2">
              <button @click="recorteConfig[recorteTab].fit='cover'"
                      :class="recorteConfig[recorteTab].fit==='cover'?'bg-mt-orange text-white':'bg-mt-cream text-mt-brown hover:bg-slate-200'"
                      class="flex-1 py-2 rounded-xl font-bold text-xs uppercase tracking-wide transition-colors">
                Recortar para llenar (Cover)
              </button>
              <button @click="recorteConfig[recorteTab].fit='contain'"
                      :class="recorteConfig[recorteTab].fit==='contain'?'bg-mt-orange text-white':'bg-mt-cream text-mt-brown hover:bg-slate-200'"
                      class="flex-1 py-2 rounded-xl font-bold text-xs uppercase tracking-wide transition-colors">
                Ver completa (Contain)
              </button>
            </div>
          </div>
          
          <!-- Zoom/Escala (scale) -->
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-500 mb-1">
              <span>Zoom / Escala</span>
              <span class="font-black text-mt-orange" x-text="recorteConfig[recorteTab].zoom + '%'"></span>
            </div>
            <input type="range" min="50" max="300" step="5" x-model.number="recorteConfig[recorteTab].zoom"
                   class="w-full accent-mt-orange cursor-pointer">
          </div>
          
          <!-- Posición Horizontal X -->
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-500 mb-1">
              <span>Posición Horizontal (X)</span>
              <span class="font-black text-mt-orange" x-text="recorteConfig[recorteTab].x + '%'"></span>
            </div>
            <input type="range" min="0" max="100" step="1" x-model.number="recorteConfig[recorteTab].x"
                   class="w-full accent-mt-orange cursor-pointer">
          </div>
          
          <!-- Posición Vertical Y -->
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-500 mb-1">
              <span>Posición Vertical (Y)</span>
              <span class="font-black text-mt-orange" x-text="recorteConfig[recorteTab].y + '%'"></span>
            </div>
            <input type="range" min="0" max="100" step="1" x-model.number="recorteConfig[recorteTab].y"
                   class="w-full accent-mt-orange cursor-pointer">
          </div>
        </div>
        
        <!-- Acciones del Modal -->
        <div class="flex gap-3 pt-3">
          <button @click="guardarRecorte()"
                  class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors text-sm shadow-md">
            Guardar Ajustes
          </button>
          <button @click="restablecerRecorte()"
                  class="px-5 py-3 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-colors text-sm">
            Restablecer
          </button>
        </div>
      </div>
    </template>
  </div>
</div>
