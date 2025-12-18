package com.vti.config;

import jakarta.servlet.MultipartConfigElement;
import org.springframework.boot.web.servlet.MultipartConfigFactory;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.util.unit.DataSize;

@Configuration
public class MultipartConfig {

    @Bean
    public MultipartConfigElement multipartConfigElement() {
        MultipartConfigFactory factory = new MultipartConfigFactory();
        
        // Set kích thước lớn
        factory.setMaxFileSize(DataSize.ofMegabytes(1000));
        factory.setMaxRequestSize(DataSize.ofMegabytes(1000));
        
        return factory.createMultipartConfig();
    }
}