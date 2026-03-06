<?php
// ============================================
// CONFIGURACIÓN DE LA BASE DE DATOS
// ============================================
$host = 'sql105.infinityfree.com';
$dbname = 'if0_40979597_XXX';
$username = 'if0_40979597';
$password = 'fVbElCR27mwe';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear tablas si no existen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'employee') NOT NULL,
            full_name VARCHAR(100)
        );
        
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    
    // Insertar usuario admin por defecto si no existe
    $checkAdmin = $pdo->query("SELECT * FROM users WHERE username = 'admin'");
    if ($checkAdmin->rowCount() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'admin', 'Administrador')")
            ->execute(['admin', $hashedPassword]);
    }
    
    // Insertar usuario empleado por defecto si no existe
    $checkEmployee = $pdo->query("SELECT * FROM users WHERE username = 'empleado'");
    if ($checkEmployee->rowCount() == 0) {
        $hashedPassword = password_hash('empleado123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'employee', 'Empleado')")
            ->execute(['empleado', $hashedPassword]);
    }
    
    // Insertar productos de ejemplo si no hay ninguno
    $checkProducts = $pdo->query("SELECT COUNT(*) FROM products");
    if ($checkProducts->fetchColumn() == 0) {
        $sampleProducts = [
            ['Laptop HP Pavilion', '15.6\", Intel Core i5, 8GB RAM, 512GB SSD', 899.99, 'Electrónica'],
            ['Mouse Inalámbrico', 'Conexión USB, 1600 DPI, Negro', 25.99, 'Electrónica'],
            ['Camisa Polo', 'Algodón, Manga Corta, Azul', 29.99, 'Ropa'],
            ['Pantalón Jeans', 'Talla 32, Azul Claro', 49.99, 'Ropa'],
            ['Smart TV 50"', '4K Ultra HD, Smart TV, HDR', 599.99, 'Electrónica']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category) VALUES (?, ?, ?, ?)");
        foreach ($sampleProducts as $product) {
            $stmt->execute($product);
        }
    }
    
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ============================================
// API ENDPOINTS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch($action) {
        case 'login':
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'name' => $user['full_name']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
            }
            exit;
            
        case 'getProducts':
            $stmt = $pdo->query("SELECT * FROM products ORDER BY category, name");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'products' => $products]);
            exit;
            
        case 'addProduct':
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $price = $input['price'] ?? 0;
            $category = $input['category'] ?? '';
            
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            exit;
            
        case 'updateProduct':
            $id = $input['id'] ?? 0;
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $price = $input['price'] ?? 0;
            $category = $input['category'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $category, $id]);
            echo json_encode(['success' => true]);
            exit;
            
        case 'updatePrice':
            $id = $input['id'] ?? 0;
            $price = $input['price'] ?? 0;
            
            $stmt = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
            $stmt->execute([$price, $id]);
            echo json_encode(['success' => true]);
            exit;
            
        case 'deleteProduct':
            $id = $input['id'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Control de Precios</title>
    <style>
        /* (Mismo CSS que la versión anterior) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #2f855a 100%);
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .content {
            padding: 30px;
        }
        
        .login-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .login-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .admin-tools {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 2px dashed #667eea;
        }
        
        .admin-tools h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .product-category {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .product-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            padding-right: 80px;
        }
        
        .product-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #48bb78;
            margin-bottom: 15px;
        }
        
        .product-price-input {
            font-size: 20px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            width: 150px;
            margin-right: 10px;
        }
        
        .product-price-input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .product-actions button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #4299e1;
            color: white;
        }
        
        .btn-edit:hover {
            background: #3182ce;
        }
        
        .btn-delete {
            background: #f56565;
            color: white;
        }
        
        .btn-delete:hover {
            background: #e53e3e;
        }
        
        .btn-save {
            background: #48bb78;
            color: white;
        }
        
        .btn-save:hover {
            background: #2f855a;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            color: #333;
        }
        
        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            animation: slideIn 0.5s ease;
        }
        
        .success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }
        
        .error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .table-view {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table-view th,
        .table-view td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table-view th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .table-view tr:hover {
            background: #f8f9fa;
        }
        
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .view-toggle button {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .view-toggle button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .category-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .category-tag {
            background: #e2e8f0;
            color: #4a5568;
            padding: 5px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .category-tag:hover,
        .category-tag.active {
            background: #667eea;
            color: white;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-left: 10px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .table-view {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span>💰</span>
                Sistema de Control de Precios
            </h1>
            <div class="user-info" id="userInfo"></div>
        </div>
        
        <div class="content">
            <div id="messageContainer"></div>
            
            <!-- Pantalla de Login -->
            <div id="loginScreen">
                <div class="login-form">
                    <h2>Iniciar Sesión</h2>
                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" id="username" placeholder="Ingresa tu usuario">
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" id="password" placeholder="Ingresa tu contraseña">
                    </div>
                    <button onclick="login()" class="btn" style="width: 100%;">Ingresar</button>
                    <p style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                        <strong>Admin:</strong> admin / admin123<br>
                        <strong>Empleado:</strong> empleado / empleado123
                    </p>
                </div>
            </div>
            
            <!-- Pantalla Principal -->
            <div id="mainScreen" style="display: none;">
                <div id="adminTools" class="admin-tools" style="display: none;">
                    <h3>
                        <span>⚙️</span>
                        Herramientas de Administrador
                    </h3>
                    <button class="btn btn-success" onclick="openModal('addModal')">
                        + Agregar Nuevo Producto
                    </button>
                </div>
                
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Buscar productos..." onkeyup="filterProducts()">
                    <button class="btn" onclick="filterProducts()">Buscar</button>
                </div>
                
                <div class="category-filter" id="categoryFilter"></div>
                
                <div class="view-toggle">
                    <button class="active" onclick="toggleView('grid')">Vista Cuadrícula</button>
                    <button onclick="toggleView('table')">Vista Tabla</button>
                </div>
                
                <div id="productsContainer">
                    <div class="loading">Cargando productos...</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar producto -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Agregar Nuevo Producto</h3>
                <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="form-group">
                <label>Nombre del Producto *</label>
                <input type="text" id="productName" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea id="productDescription" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Precio *</label>
                <input type="number" id="productPrice" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <input type="text" id="productCategory" placeholder="Ej: Electrónica, Ropa, etc.">
            </div>
            <button onclick="addProduct()" class="btn" style="width: 100%;">Guardar Producto</button>
        </div>
    </div>
    
    <!-- Modal para editar producto -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Producto</h3>
                <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            </div>
            <input type="hidden" id="editProductId">
            <div class="form-group">
                <label>Nombre del Producto *</label>
                <input type="text" id="editProductName" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea id="editProductDescription" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Precio *</label>
                <input type="number" id="editProductPrice" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <input type="text" id="editProductCategory">
            </div>
            <button onclick="saveProductEdit()" class="btn" style="width: 100%;">Actualizar Producto</button>
        </div>
    </div>
    
    <script>
        // ============================================
        // VARIABLES GLOBALES
        // ============================================
        let currentUser = null;
        let currentView = 'grid';
        let currentCategory = 'all';
        let searchTerm = '';
        let products = [];
        
        // ============================================
        // FUNCIONES DE API
        // ============================================
        async function apiCall(action, data = {}) {
            const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action, ...data })
            });
            return await response.json();
        }
        
        // ============================================
        // FUNCIONES DE AUTENTICACIÓN
        // ============================================
        async function login() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            const result = await apiCall('login', { username, password });
            
            if (result.success) {
                currentUser = result.user;
                document.getElementById('loginScreen').style.display = 'none';
                document.getElementById('mainScreen').style.display = 'block';
                
                document.getElementById('userInfo').innerHTML = `
                    <span class="role-badge">👤 ${currentUser.name} (${currentUser.role === 'admin' ? 'Administrador' : 'Empleado'})</span>
                    <button class="btn btn-logout" onclick="logout()">Cerrar Sesión</button>
                `;
                
                if (currentUser.role === 'admin') {
                    document.getElementById('adminTools').style.display = 'block';
                }
                
                await loadProducts();
                showMessage('success', `¡Bienvenido, ${currentUser.name}!`);
            } else {
                showMessage('error', result.message || 'Credenciales incorrectas');
            }
        }
        
        function logout() {
            currentUser = null;
            document.getElementById('loginScreen').style.display = 'block';
            document.getElementById('mainScreen').style.display = 'none';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('adminTools').style.display = 'none';
            document.getElementById('userInfo').innerHTML = '';
        }
        
        // ============================================
        // FUNCIONES DE PRODUCTOS
        // ============================================
        async function loadProducts() {
            const result = await apiCall('getProducts');
            if (result.success) {
                products = result.products;
                updateCategoryFilter();
                filterProducts();
            }
        }
        
        function updateCategoryFilter() {
            const categories = ['all', ...new Set(products.map(p => p.category).filter(c => c))];
            const filterContainer = document.getElementById('categoryFilter');
            
            filterContainer.innerHTML = categories.map(cat => `
                <span class="category-tag ${currentCategory === cat ? 'active' : ''}" 
                      onclick="filterByCategory('${cat}')">
                    ${cat === 'all' ? 'Todos' : cat}
                </span>
            `).join('');
        }
        
        function filterByCategory(category) {
            currentCategory = category;
            updateCategoryFilter();
            filterProducts();
        }
        
        function filterProducts() {
            searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            const filteredProducts = products.filter(product => {
                const matchesCategory = currentCategory === 'all' || product.category === currentCategory;
                const matchesSearch = product.name.toLowerCase().includes(searchTerm) ||
                                     (product.description && product.description.toLowerCase().includes(searchTerm));
                return matchesCategory && matchesSearch;
            });
            
            displayProducts(filteredProducts);
        }
        
        function displayProducts(productsToShow) {
            const container = document.getElementById('productsContainer');
            
            if (productsToShow.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No se encontraron productos</p>';
                return;
            }
            
            if (currentView === 'grid') {
                container.innerHTML = `
                    <div class="products-grid">
                        ${productsToShow.map(product => `
                            <div class="product-card">
                                ${product.category ? `<span class="product-category">${product.category}</span>` : ''}
                                <div class="product-name">${escapeHtml(product.name)}</div>
                                ${product.description ? `<div class="product-description">${escapeHtml(product.description)}</div>` : ''}
                                
                                ${currentUser?.role === 'admin' ? `
                                    <div>
                                        <input type="number" class="product-price-input" 
                                               id="price-${product.id}" 
                                               value="${product.price}" 
                                               step="0.01" 
                                               min="0">
                                    </div>
                                    <div class="product-actions">
                                        <button class="btn-save" onclick="quickUpdatePrice(${product.id})">Actualizar Precio</button>
                                        <button class="btn-edit" onclick="openEditModal(${product.id})">Editar</button>
                                        <button class="btn-delete" onclick="deleteProduct(${product.id})">Eliminar</button>
                                    </div>
                                ` : `
                                    <div class="product-price">$${Number(product.price).toFixed(2)}</div>
                                `}
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <table class="table-view">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                ${currentUser?.role === 'admin' ? '<th>Acciones</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
                            ${productsToShow.map(product => `
                                <tr>
                                    <td><strong>${escapeHtml(product.name)}</strong></td>
                                    <td>${escapeHtml(product.description) || '-'}</td>
                                    <td>${escapeHtml(product.category) || '-'}</td>
                                    <td>
                                        ${currentUser?.role === 'admin' ? `
                                            <input type="number" class="product-price-input" 
                                                   id="price-${product.id}" 
                                                   value="${product.price}" 
                                                   step="0.01" 
                                                   min="0"
                                                   style="width: 100px;">
                                        ` : `
                                            <strong>$${Number(product.price).toFixed(2)}</strong>
                                        `}
                                    </td>
                                    ${currentUser?.role === 'admin' ? `
                                        <td>
                                            <button class="btn-save" onclick="quickUpdatePrice(${product.id})" style="padding: 5px 10px; margin-right: 5px;">💾</button>
                                            <button class="btn-edit" onclick="openEditModal(${product.id})" style="padding: 5px 10px; margin-right: 5px;">✏️</button>
                                            <button class="btn-delete" onclick="deleteProduct(${product.id})" style="padding: 5px 10px;">🗑️</button>
                                        </td>
                                    ` : ''}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        }
        
        function escapeHtml(text) {
            if (!text) return text;
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function toggleView(view) {
            currentView = view;
            const buttons = document.querySelectorAll('.view-toggle button');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            filterProducts();
        }
        
        // ============================================
        // FUNCIONES DE ADMINISTRACIÓN
        // ============================================
        function openModal(modalId) {
            if (currentUser?.role !== 'admin') {
                showMessage('error', 'No tienes permisos para realizar esta acción');
                return;
            }
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        async function addProduct() {
            const name = document.getElementById('productName').value;
            const description = document.getElementById('productDescription').value;
            const price = parseFloat(document.getElementById('productPrice').value);
            const category = document.getElementById('productCategory').value;
            
            if (!name || !price || price <= 0) {
                showMessage('error', 'Nombre y precio válido son obligatorios');
                return;
            }
            
            const result = await apiCall('addProduct', {
                name, description, price, category
            });
            
            if (result.success) {
                document.getElementById('productName').value = '';
                document.getElementById('productDescription').value = '';
                document.getElementById('productPrice').value = '';
                document.getElementById('productCategory').value = '';
                
                closeModal('addModal');
                await loadProducts();
                showMessage('success', 'Producto agregado correctamente');
            }
        }
        
        async function openEditModal(productId) {
            if (currentUser?.role !== 'admin') {
                showMessage('error', 'No tienes permisos para realizar esta acción');
                return;
            }
            
            const product = products.find(p => p.id == productId);
            if (product) {
                document.getElementById('editProductId').value = product.id;
                document.getElementById('editProductName').value = product.name;
                document.getElementById('editProductDescription').value = product.description || '';
                document.getElementById('editProductPrice').value = product.price;
                document.getElementById('editProductCategory').value = product.category || '';
                openModal('editModal');
            }
        }
        
        async function saveProductEdit() {
            const id = parseInt(document.getElementById('editProductId').value);
            const name = document.getElementById('editProductName').value;
            const description = document.getElementById('editProductDescription').value;
            const price = parseFloat(document.getElementById('editProductPrice').value);
            const category = document.getElementById('editProductCategory').value;
            
            if (!name || !price || price <= 0) {
                showMessage('error', 'Nombre y precio válido son obligatorios');
                return;
            }
            
            const result = await apiCall('updateProduct', {
                id, name, description, price, category
            });
            
            if (result.success) {
                closeModal('editModal');
                await loadProducts();
                showMessage('success', 'Producto actualizado correctamente');
            }
        }
        
        async function quickUpdatePrice(productId) {
            if (currentUser?.role !== 'admin') {
                showMessage('error', 'No tienes permisos para realizar esta acción');
                return;
            }
            
            const newPrice = parseFloat(document.getElementById(`price-${productId}`).value);
            
            if (newPrice <= 0) {
                showMessage('error', 'El precio debe ser mayor a 0');
                return;
            }
            
            const result = await apiCall('updatePrice', {
                id: productId,
                price: newPrice
            });
            
            if (result.success) {
                await loadProducts();
                showMessage('success', 'Precio actualizado correctamente');
                
                setTimeout(() => {
                    const priceElement = document.getElementById(`price-${productId}`);
                    if (priceElement) {
                        priceElement.style.backgroundColor = '#ffff99';
                        setTimeout(() => {
                            priceElement.style.backgroundColor = '';
                        }, 1000);
                    }
                }, 100);
            }
        }
        
        async function deleteProduct(productId) {
            if (currentUser?.role !== 'admin') {
                showMessage('error', 'No tienes permisos para realizar esta acción');
                return;
            }
            
            if (confirm('¿Estás seguro de eliminar este producto?')) {
                const result = await apiCall('deleteProduct', { id: productId });
                if (result.success) {
                    await loadProducts();
                    showMessage('success', 'Producto eliminado correctamente');
                }
            }
        }
        
        // ============================================
        // FUNCIONES UTILITARIAS
        // ============================================
        function showMessage(type, text) {
            const container = document.getElementById('messageContainer');
            container.innerHTML = `<div class="message ${type}">${text}</div>`;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 3000);
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
        
        // Cargar productos si ya hay sesión (por si recarga la página)
        window.onload = function() {
            // Verificar si hay usuario en sessionStorage
            const savedUser = sessionStorage.getItem('currentUser');
            if (savedUser) {
                currentUser = JSON.parse(savedUser);
                document.getElementById('loginScreen').style.display = 'none';
                document.getElementById('mainScreen').style.display = 'block';
                
                document.getElementById('userInfo').innerHTML = `
                    <span class="role-badge">👤 ${currentUser.name} (${currentUser.role === 'admin' ? 'Administrador' : 'Empleado'})</span>
                    <button class="btn btn-logout" onclick="logout()">Cerrar Sesión</button>
                `;
                
                if (currentUser.role === 'admin') {
                    document.getElementById('adminTools').style.display = 'block';
                }
                
                loadProducts();
            }
        };
    </script>
</body>
</html>