<div class="export-template-container" 
     style="font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; display: flex; flex-direction: column; gap: 16px; margin-top: 12px; margin-bottom: 12px;"
     x-data="{
        state: @entangle(\Illuminate\Support\Str::replaceLast('selected_fields_picker', 'selected_fields', $getStatePath())).live,
        productTypeId: @entangle(\Illuminate\Support\Str::replaceLast('selected_fields_picker', 'product_type_id', $getStatePath())),
        searchQuery: '',
        productTypes: @js($productTypes),
        availableFields: [],
        selectedFields: [],
        draggedIndex: null,
        dragOverIndex: null,
        
        init() {
            this.buildFields();
            this.$watch('productTypeId', () => {
                this.buildFields();
            });
        },

        buildFields() {
            const standardFields = [
                { id: 'code', label: 'Product Code', required: true },
                { id: 'name', label: 'Product Name', required: true },
                { id: 'category_name', label: 'Category Name', required: true },
                { id: 'sub_category_name', label: 'Sub-Category Name', required: false },
                { id: 'location_code', label: 'Location Code', required: false },
                { id: 'barcode', label: 'Barcode', required: false },
                { id: 'qr_code', label: 'QR Code', required: false },
                { id: 'quantity', label: 'Quantity', required: false },
                { id: 'description', label: 'Description', required: false }
            ];

            let dynamicFields = [];
            if (this.productTypeId) {
                const pt = this.productTypes.find(t => t.id == this.productTypeId);
                if (pt && pt.product_type_fields) {
                    dynamicFields = pt.product_type_fields.map(f => ({
                        id: f.field_name.toLowerCase(),
                        label: f.field_label,
                        required: !!f.required
                    }));
                }
            }

            const allFields = [...standardFields, ...dynamicFields];
            const requiredFields = allFields.filter(f => f.required);
            const newSelected = [];
            
            requiredFields.forEach(rf => {
                newSelected.push(rf);
            });

            // If there is a previous selection, preserve chosen fields
            if (Array.isArray(this.selectedFields) && this.selectedFields.length > 0) {
                this.selectedFields.forEach(sf => {
                    if (!sf.required && allFields.some(f => f.id === sf.id)) {
                        newSelected.push(allFields.find(f => f.id === sf.id));
                    }
                });
            }

            this.selectedFields = newSelected;
            this.availableFields = allFields.filter(f => !this.selectedFields.some(sf => sf.id === f.id));
            this.updateState();
        },

        addField(field) {
            this.selectedFields.push(field);
            this.availableFields = this.availableFields.filter(f => f.id !== field.id);
            this.updateState();
        },

        removeField(field) {
            if (field.required) return;
            this.availableFields.push(field);
            this.selectedFields = this.selectedFields.filter(f => f.id !== field.id);
            this.updateState();
        },

        moveUp(index) {
            if (index === 0) return;
            const temp = this.selectedFields[index];
            this.selectedFields[index] = this.selectedFields[index - 1];
            this.selectedFields[index - 1] = temp;
            this.updateState();
        },

        moveDown(index) {
            if (index === this.selectedFields.length - 1) return;
            const temp = this.selectedFields[index];
            this.selectedFields[index] = this.selectedFields[index + 1];
            this.selectedFields[index + 1] = temp;
            this.updateState();
        },

        dragStart(index, event) {
            this.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', index);
        },

        dragOver(index, event) {
            event.preventDefault();
            this.dragOverIndex = index;
        },

        dragLeave() {
            this.dragOverIndex = null;
        },

        dragEnd() {
            this.draggedIndex = null;
            this.dragOverIndex = null;
        },

        drop(index, event) {
            event.preventDefault();
            if (this.draggedIndex === null || this.draggedIndex === index) return;
            const item = this.selectedFields.splice(this.draggedIndex, 1)[0];
            this.selectedFields.splice(index, 0, item);
            this.draggedIndex = null;
            this.dragOverIndex = null;
            this.updateState();
        },

        updateState() {
            const val = this.selectedFields.map(f => f.id).join(',');
            this.state = val;
            $wire.set('{{ \Illuminate\Support\Str::replaceLast("selected_fields_picker", "selected_fields", $getStatePath()) }}', val);
        },

        get filteredAvailable() {
            if (!this.searchQuery) return this.availableFields;
            const q = this.searchQuery.toLowerCase();
            return this.availableFields.filter(f => f.label.toLowerCase().includes(q));
        }
     }"
     x-init="init()">
    
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Left: Available Fields -->
        <div style="flex: 1; min-width: 280px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background-color: #f9fafb; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: #374151;">Available fields</h3>
            <input type="text" 
                   x-model="searchQuery" 
                   placeholder="Search available fields..." 
                   style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; background-color: #ffffff; color: #1f2937; outline: none; transition: border-color 0.2s;"
                   onfocus="this.style.borderColor='#f59e0b'"
                   onblur="this.style.borderColor='#d1d5db'" />
            
            <div style="max-height: 310px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 4px;">
                <template x-for="field in filteredAvailable" :key="field.id">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-radius: 8px; background-color: #ffffff; border: 1px solid #f3f4f6; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                        <span style="font-size: 13px; font-weight: 500; color: #4b5563;" x-text="field.label"></span>
                        <button type="button" 
                                @click="addField(field)"
                                style="border: none; background: none; padding: 4px; cursor: pointer; color: #10b981; display: flex; align-items: center; justify-content: center; border-radius: 6px;"
                                onmouseover="this.style.backgroundColor='#ecfdf5'"
                                onmouseout="this.style.backgroundColor='transparent'">
                            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                </template>
                <div x-show="filteredAvailable.length === 0" style="font-size: 12px; color: #9ca3af; text-align: center; padding: 16px 0;">
                    No available fields found.
                </div>
            </div>
        </div>

        <!-- Right: Selected Fields to Export (Drag & Drop Reorder) -->
        <div style="flex: 1; min-width: 280px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background-color: #f9fafb; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: #374151;">Fields to export</h3>
            <span style="font-size: 11px; color: #6b7280; margin-top: -8px;">Tip: Drag and drop field labels to reorder them.</span>
            
            <div style="max-height: 310px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 4px;">
                <template x-for="(field, index) in selectedFields" :key="field.id">
                    <div draggable="true"
                         @dragstart="dragStart(index, $event)"
                         @dragover="dragOver(index, $event)"
                         @dragleave="dragLeave()"
                         @dragend="dragEnd()"
                         @drop="drop(index, $event)"
                         style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; border: 1px dashed; cursor: grab; transition: all 0.2s;"
                         :style="draggedIndex === index 
                            ? 'opacity: 0.4; border-color: #9ca3af; background-color: #f3f4f6;' 
                            : (dragOverIndex === index 
                                ? 'border-color: #f59e0b; background-color: #fffbef; transform: scale(1.01);' 
                                : (field.required 
                                    ? 'background-color: #fffbeb; border-color: #fef3c7; color: #b45309;' 
                                    : 'background-color: #ffffff; border-color: #e5e7eb; color: #1f2937;'))">
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <!-- Drag Handle Icon -->
                            <div style="color: #9ca3af; cursor: grab; display: flex; align-items: center;">
                                <svg style="width: 14px; height: 14px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a2 2 0 11.001 3.999A2 2 0 017 2zm6 0a2 2 0 11.001 3.999A2 2 0 0113 2zm-6 6a2 2 0 11.001 3.999A2 2 0 017 8zm6 0a2 2 0 11.001 3.999A2 2 0 0113 8zm-6 6a2 2 0 11.001 3.999A2 2 0 017 14zm6 0a2 2 0 11.001 3.999A2 2 0 0113 14z" />
                                </svg>
                            </div>
                            
                            <span style="font-size: 13px; font-weight: 600; user-select: none;" x-text="field.label"></span>
                            
                            <template x-if="field.required">
                                <span style="font-size: 9px; background-color: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; user-select: none;">Required</span>
                            </template>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <!-- Move Buttons (Fallback) -->
                            <button type="button" @click="moveUp(index)" :disabled="index === 0" style="border: none; background: none; padding: 2px; cursor: pointer; color: #9ca3af;" title="Move Up">
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                </svg>
                            </button>
                            <button type="button" @click="moveDown(index)" :disabled="index === selectedFields.length - 1" style="border: none; background: none; padding: 2px; cursor: pointer; color: #9ca3af;" title="Move Down">
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <button type="button" 
                                    @click="removeField(field)"
                                    :disabled="field.required"
                                    style="border: none; background: none; padding: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 6px;"
                                    :style="field.required ? 'color: #d1d5db; cursor: not-allowed;' : 'color: #9ca3af;'"
                                    onmouseover="if(!this.disabled) { this.style.color='#dc2626'; this.style.backgroundColor='#fef2f2'; }"
                                    onmouseout="if(!this.disabled) { this.style.color='#9ca3af'; this.style.backgroundColor='transparent'; }">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
